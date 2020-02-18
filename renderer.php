<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Defines the renderer for the review module.
 *
 * @package    mod_review
 * @copyright  2019 Oleg Kovalenko ©HSE University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use mod_review\user_review;

/**
 * The renderer for the review module.
 *
 * @copyright  2019 Oleg Kovalenko ©HSE University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_review_renderer extends plugin_renderer_base{

    /**
     * Render review page
     * @param $review object review module object
     * @return string HTML of review page
     */
    public function review_page($review){
        global $USER;
        //add heading
        $content=$this->heading(get_string('modulename','mod_review'));
        $user_review=new user_review($USER,$review);
        //add widget for review
        $content.=
            $this->user_rate_form($user_review).
            $this->user_review_form($user_review).
            html_writer::empty_tag('hr');
        //get context of course
        $context = $this->page->context->get_parent_context();
        //if user has a capability to view all reviews
        if (has_capability('mod/review:viewall', $context)) {
            //display all reviews and rating block
            $content.=$this->display_all_user_reviews($review);
        }
        return html_writer::div($content,'page_container');
    }

    /**
     * Display form to make rate
     * @param $user_review object of user review class
     * @param bool $header form header
     * @param string $aux_class auxiliary css class
     * @return string HTML of rate form
     */
    public function user_rate_form(user_review $user_review, $header=true, $aux_class=''){
        $form='';
        if ($header){ //add header if it's received
            $form=html_writer::div(html_writer::tag('h4',get_string('your_rate','mod_review')));
        }
        $rate_stars='';
        for($i=5;$i>=1;$i--){
            //display empty/not empty star in accordance with current user rate
            $aux_star_class = $user_review->rate>=$i ? ' star_notempty' : '';
            $url=new moodle_url($this->page->url,['rate'=>$i]); //url to save rate - when JS is turned off
            $rate_stars.=html_writer::link($url,'&nbsp;',
                ['class'=>'yfdr_'.$user_review->reviewid.'_'.$i.' star'.$aux_star_class]);
        }
        $css_class='your_rate_stars'.($aux_class ? ' '.$aux_class : ''); //get css class
        //add form
        $form.=html_writer::div($rate_stars,$css_class,['id'=>'rate'.$user_review->reviewid]);
        return html_writer::div($form,'rate_container');
    }

    /**
     * Display form to send review
     * @param $user_review object of user review
	 * @param $course object of course
	 * @param $cm object of course module
     * @return string HTML if form
     */
    public function user_review_form(user_review $user_review){
        return html_writer::div($user_review->review_form($this->page),'review_container review_form');
    }

    /**
     * Display all rates statistics
     * @param $reviewid int ID of review
     * @return string HTML of statistics block
     */
    public function display_all_rates_stat($stat){
        $html=
            $this->display_all_rates_avg($stat->avg).  //add all rates average widget
            $this->display_all_rates_scales($stat);    //add all rates scales
        //add header and wrap all in container
        $html=
            html_writer::div(
                html_writer::div(get_string('all_rates','mod_review').':&nbsp;'.$stat->amount,
                    'all_rates_stat_header').$html
            ,'display_all_rates');
        return $html;
    }

    /**
     * Display all rates average widget
     * @param float @avg rate average
     * @return string HTML of rates average widget
     */
    public function display_all_rates_avg($avg){
        $html=''; //initialize HTML variable
        for($i=1;$i<=5;$i++){ //from 1 to 5
            if($avg>=$i){ //if average bigger than current iteration
                //display not empty star
                $html.=html_writer::tag('span','&nbsp;',['class'=>'star star_notempty']);
            } elseif($avg>=$i-1) { //if average bigger than previous iteration
                //display not empty star
                $html.=html_writer::tag('span','&nbsp;',['class'=>'star star_notempty']);
                $offset=(25-25*($avg-($i-1)))+5; //calculate an offset for white background
                //display an white rectangle with offset to the left
				//     |<--offset--|___
                //    ★            |__| ☆
                $html.=html_writer::tag('span','&nbsp;',['class'=>'rate_whitebg','style'=>'left:-'.$offset.'px']);
                //display an empty star above it
                $html.=html_writer::tag('span','&nbsp;',['class'=>'star']);
            } else { //if average smaller than a privious iteration
                //display an empty star
                $html.=html_writer::tag('span','&nbsp;',['class'=>'star']);
            }
        }
        //display average text value
        $text=html_writer::div(get_string('rate_avg','mod_review').':&nbsp;'.$avg,'rate_avg_text');
        return html_writer::div($html.$text,'rate_stars_avg');
    }

    /**
     * Display all rates scales
     * @param $stat object statistics info
     * @return string HTML of rate scales
     */
    public function display_all_rates_scales($stat){
        $html='';
        $maincolor=get_config('mod_review','colortheme');
        for($i=1;$i<=5;$i++){
            $offset=$stat->{'rate'.$i}*2; //calculate an offset
            //display a rectangle with gradient in proportion to the share of rate in the total number of rates
            $html.=html_writer::div(
                $i.
                html_writer::div('&nbsp;','rate_scale',
                    ['style'=>'background-image: linear-gradient(to right,'.$maincolor.' 0px,'.$maincolor.' '.$offset.'px,#fff '.$offset.'px,#FFF 100%);']).
                $stat->{'rate'.$i}.'%'
            ,'rate_scale_info');
        }
        return html_writer::div($html,'all_rates_scales');
    }

    /**
     * Display all user reviews
     * @param $review object review
     * @return string HTML of all reviews block
     */
    public function display_all_user_reviews($review){
        $stat=user_review::rates_stat($review->id); //get statistics
        $html=html_writer::div($this->display_all_rates_stat($stat),'',['id'=>'rates_stat_container']); //add statistics to html;
        $filter=['reviewid'=>$review->id,'status'=>[user_review::REVIEW_ACCEPTED]]; //set filter to get user reviews
        $page=optional_param('page',0,PARAM_INT); //page param for paging
        $count_entries=user_review::count($filter); //count all reviews
        $perpage=get_config('mod_review','perpage_review');
        //get all reviews in view of paging
        $user_reviews=user_review::get($filter,$page,$perpage);
        //add HTML of each review
        foreach($user_reviews as $user_review){$html.=$this->display_review($user_review);}

        $url=$this->page->url; //get page url
        $url->set_anchor('allreviews'); //add anchor to the url
        $html.=$this->paging_bar($count_entries, $page, $perpage, $url); //add paging bar

        $options=isset($_GET['page']) ? ['open'=>'open'] : []; //if user send page (use paging navigation) - show him all reviews block
        //display all reviews block as a detail tag (browser would allow to show hide it automatically)
        $summary=html_writer::tag('summary',get_string('all_reviews','mod_review'),['id'=>'allreviews']);
        $details=html_writer::tag('details',$summary.$html,$options);
        return html_writer::div($details,'display_all_reviews');
    }

    /**
     * Display one user review
     * @param $user_review object of user review
     * @return string HTML of user review
     */
    public function display_review(user_review $user_review){
        //display photo and name of author,his rate,time of review and text of review
        $review_html=
            html_writer::div($this->user_picture($user_review->user, ['size' => 30,'link'=>false]),'review_usericon').
            html_writer::div($user_review->user->lastname.' '.$user_review->user->firstname,'review_userfio').
            html_writer::div($this->rate_stars($user_review->rate),'review_rate',['id'=>'review_rate'.$user_review->id]).
            html_writer::div(strftime('%e %B %Y',$user_review->timeadded),'review_date').
            html_writer::div($user_review->text,'review_text');
        return html_writer::div($review_html,'display_review');
    }

    /**
     * Display rate with stars
     * @param $rate int rate value
     * @return string HTML of rate
     */
    public function rate_stars($rate){
        $rate_stars='';
        for($i=1;$i<=5;$i++){
            //display empty/not empty star in accordance with rate
            $css_class='star'.($rate>=$i ? ' star_notempty' : '');
            $rate_stars.=html_writer::tag('span','&nbsp;',['class'=>$css_class,'data-rate'=>$i]);
        }
        return $rate_stars;
    }

    /**
     * Render moderation page
     * @param null $review object of reviewe
     * @param null $aux_filter auxiliary filter
     * @return string HTML of moderation page
     */
    public function moderate_page($review=null, $aux_filter=null,$perpage=0){
        user_review::save_status(); //we may need to save status from GET-params (when JS is turned off)
        $head=[];
        $filter=['notempty_reviews'=>1];
        $content='';
        if(!$perpage){$perpage=get_config('mod_review','perpage_moderate');}
        if ($review){//if received review - show only reviews of one course
            $content.=$this->heading(get_string('pluginname','mod_review'));
            $filter['reviewid']=$review->id;
        } else { //if review not received - show reviews of all site (could be used in other plugins to display reviews)
            $head['category']=get_string('category');
            $head['course']=get_string('course');
        }
        if($aux_filter){$filter+=$aux_filter;} //add auxiliary filter (could be used in other plugins to display reviews)

        $page=optional_param('page',0,PARAM_INT); //page param for paging
        $count_entries=user_review::count($filter);
        //get user reviews with paging
        $user_reviews=user_review::get($filter,$page,$perpage);

        $table=new html_table;  //initialize a new HTML table
        $table->attributes['class']='reviewtable';
        $head+=[
            get_string('author','mod_review'),
            get_string('review','mod_review'),
            get_string('rate','mod_review'),
            get_string('status','mod_review')
        ];
        $table->head=$head;

        if(empty($user_reviews)) {   //if no user reviews
            //display information
            $emptycell=new html_table_cell(get_string('entries_notfound','mod_review'));
            $emptycell->colspan=count($table->head);
            $table->data[]=new html_table_row([$emptycell]);
        } else {  //if reviews found
            foreach ($user_reviews as $user_review) { //for each review
                $row = new html_table_row; //add review info in table
                //category & course (if needed), author, text, rate, and status switcher widget
                $row->cells=($review)
                    ? []
                    : ['category'=>html_writer::link(new moodle_url('/course/management.php',['categoryid'=>$user_review->categoryid]),
                        $user_review->categoryname,['target'=>'_blank']),
                        'course'=>html_writer::link(new moodle_url('/course/view.php',['id'=>$user_review->review->course]),
                        $user_review->coursename,['target'=>'_blank'])
                      ];
                $row->cells+=[
                    $user_review->user->lastname.' '.$user_review->user->firstname.
                        html_writer::empty_tag('br').
                        html_writer::tag('span',date('d.m.Y H:i:s',$user_review->timeadded),['class'=>'reviewtable_date']),
                    $user_review->text,
                    $user_review->rate,
                    html_writer::div($this->status_switcher($user_review),'status_container',
                        ['id'=>'status_container'.$user_review->id])
                ];
                $table->data[] = $row;
            }
        }
        $content.=html_writer::table($table); //add table to the page
        $content.= $this->paging_bar($count_entries, $page, $perpage, $this->page->url); //add paging bar
        return html_writer::div($content,'page_container');
    }

    /**
     * Display status switcher for moderation
     * @param $user_review object of user review
     * @return string HTML of widget
     */
    public function status_switcher(user_review $user_review){
        $html='';
        if(!$this->page->has_set_url()){ //check for page url set and if not - set it
            $cm=get_coursemodule_from_instance('review',$user_review->review->id);
            $this->page->set_url(new moodle_url('/mod/review/view.php', ['id' => $cm->id]));
        }
        //for each statuses
        foreach([user_review::REVIEW_RETURNED,user_review::REVIEW_NOTCHECKED,user_review::REVIEW_ACCEPTED] as $status) {
            //add all statuses as an empty rectangles - to catch click events
            $url=new moodle_url($this->page->url,['userreviewid'=>$user_review->id,'newstatus'=>$status]); //url to save status - when JS is turned off
            $html.=html_writer::link($url,'&nbsp;',['class'=>'status status'.$status,'data-status'=>$status,'data-reviewid'=>$user_review->id]);
        }
        //add the handler of switcher - to catch drag events
        $html.=html_writer::link('#','&nbsp;',
            ['class'=>'status handler selected draggable','data-status'=>$user_review->status,'data-reviewid'=>$user_review->id]);
        return html_writer::div($html,'status_switcher instatus'.$user_review->status).
            html_writer::div(get_string('status'.$user_review->status.'_short','mod_review'),'textstatus');
    }

}
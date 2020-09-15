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
defined('MOODLE_INTERNAL') || die();

use mod_review\user_review;

/**
 * The renderer for the review module.
 *
 * @copyright  2019 Oleg Kovalenko ©HSE University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_review_renderer extends plugin_renderer_base {

    /**
     * Render review page
     * @param object $review review module object
     * @return string HTML of review page
     */
    public function review_page($review) {
        global $USER;
        // Add heading.
        $content = $this->heading(get_string('modulename', 'mod_review'));
        $userreview = new user_review($USER, $review);
        // Add widget for review.
        $content .=
            $this->user_rate_form($userreview).
            $this->user_review_form($userreview).
            html_writer::empty_tag('hr');
        // Get context of course.
        $context = $this->page->context->get_parent_context();
        // If user has a capability to view all reviews.
        if (has_capability('mod/review:viewall', $context)) {
            // Display all reviews and rating block.
            $content .= $this->display_all_user_reviews($review);
        }
        return html_writer::div($content, 'page_container');
    }

    /**
     * Display form to make rate
     * @param user_review $userreview user review
     * @param bool $header form header
     * @param string $auxclass auxiliary css class
     * @return string HTML of rate form
     */
    public function user_rate_form(user_review $userreview, $header = true, $auxclass = '') {
        $form = '';
        if ($header) { // Add header if it's received.
            $form = html_writer::div(html_writer::tag('h4', get_string('your_rate', 'mod_review')));
        }
        $ratestars = '';
        for ($i = 5; $i >= 1; $i--) {
            // Display empty/not empty star in accordance with current user rate.
            $auxstarclass = $userreview->rate >= $i ? ' star_notempty' : '';
            $url = new moodle_url($this->page->url, ['rate' => $i]); // Url to save rate - when JS is turned off.
            $ratestars .= html_writer::link($url, '&nbsp;',
                ['class' => 'yfdr_'.$userreview->reviewid.'_'.$i.' star'.$auxstarclass]);
        }
        $cssclass = 'your_rate_stars'.($auxclass ? ' '.$auxclass : ''); // Get css class.
        // Add form.
        $form .= html_writer::div($ratestars, $cssclass, ['id' => 'rate'.$userreview->reviewid]);
        return html_writer::div($form, 'rate_container');
    }

    /**
     * Display form to send review
     * @param user_review $userreview user review
     * @return string HTML if form
     */
    public function user_review_form(user_review $userreview) {
        return html_writer::div($userreview->review_form($this->page), 'review_container review_form');
    }

    /**
     * Display all rates statistics
     * @param object $stat statistics data
     * @return string HTML of statistics block
     */
    public function display_all_rates_stat($stat) {
        $html =
            $this->display_all_rates_avg($stat->avg).  // Add all rates average widget.
            $this->display_all_rates_scales($stat); // Add all rates scales.
        // Add header and wrap all in container.
        $html =
            html_writer::div(
                html_writer::div(get_string('all_rates', 'mod_review').':&nbsp;'.$stat->amount,
                    'all_rates_stat_header').$html
            , 'display_all_rates');
        return $html;
    }

    /**
     * Display all rates average widget
     * @param float @avg rate average
     * @return string HTML of rates average widget
     */
    public function display_all_rates_avg($avg) {
        $html = ''; // Initialize HTML variable.
        for ($i = 1; $i <= 5; $i++) { // From 1 to 5.
            if ($avg >= $i) { // If average bigger than current iteration.
                // Display not empty star.
                $html .= html_writer::tag('span', '&nbsp;', ['class' => 'star star_notempty']);
            } else if ($avg >= $i - 1) { // If average bigger than previous iteration.
                // Display not empty star.
                $html .= html_writer::tag('span', '&nbsp;', ['class' => 'star star_notempty']);
                $offset = (25 - 25 * ($avg - ($i - 1))) + 5; // Calculate an offset for white background.
                // Display white rectangle with offset to the left.
                $html .= html_writer::tag('span', '&nbsp;', ['class' => 'rate_whitebg', 'style' => 'left:-'.$offset.'px']);
                // Display an empty star above it.
                $html .= html_writer::tag('span', '&nbsp;', ['class' => 'star']);
            } else { // If average smaller than a privious iteration.
                // Display an empty star.
                $html .= html_writer::tag('span', '&nbsp;', ['class' => 'star']);
            }
        }
        // Display average text value.
        $text = html_writer::div(get_string('rate_avg', 'mod_review').':&nbsp;'.$avg, 'rate_avg_text');
        return html_writer::div($html.$text, 'rate_stars_avg');
    }

    /**
     * Display all rates scales
     * @param object $stat statistics data
     * @return string HTML of rate scales
     */
    public function display_all_rates_scales($stat) {
        $html = '';
        $maincolor = get_config('mod_review', 'colortheme');
        $gradientcolor = '';
        for ($i = 1; $i <= 5; $i++) {
            $offset = $stat->{'rate'.$i} * 2; // Calculate an offset.
            // Color for CSS.
            $gradientcolor = $maincolor.' 0px,'.$maincolor.' '.$offset.'px,#fff '.$offset.'px,#FFF 100%';
            // Display a rectangle with gradient in proportion to the share of rate in the total number of rates.
            $html .= html_writer::div(
                $i.
                html_writer::div('&nbsp;', 'rate_scale',
                    ['style' => 'background-image: linear-gradient(to right,'.$gradientcolor.');']).
                $stat->{'rate'.$i}.'%', 'rate_scale_info');
        }
        return html_writer::div($html, 'all_rates_scales');
    }

    /**
     * Display all user reviews
     * @param object $review review
     * @return string HTML of all reviews block
     */
    public function display_all_user_reviews($review) {
        $stat = user_review::rates_stat($review->id); // Get statistics.
        $html = html_writer::div($this->display_all_rates_stat($stat),
            '', ['id' => 'rates_stat_container']); // Add statistics to html.
        $filter = ['reviewid' => $review->id, 'status' => [user_review::REVIEW_ACCEPTED]]; // Set filter to get user reviews.
        $page = optional_param('page', 0, PARAM_INT); // Page param for paging.
        $countentries = user_review::count($filter); // Count all reviews.
        $perpage = get_config('mod_review', 'perpage_review');
        // Get all reviews in view of paging.
        $userreviews = user_review::get($filter, $page, $perpage);
        // Add HTML of each review.
        foreach ($userreviews as $userreview) {
            $html .= $this->display_review($userreview);
        }

        $url = $this->page->url; // Get page url.
        $url->set_anchor('allreviews'); // Add anchor to the url.
        $html .= $this->paging_bar($countentries, $page, $perpage, $url); // Add paging bar.

        // If user send page (use paging navigation) - show him all reviews block.
        $options = isset($_GET['page']) ? ['open' => 'open'] : [];
        // Display all reviews block as a detail tag (browser would allow to show/hide it automatically).
        $summary = html_writer::tag('summary', get_string('all_reviews', 'mod_review'), ['id' => 'allreviews']);
        $details = html_writer::tag('details', $summary.$html, $options);
        return html_writer::div($details, 'display_all_reviews');
    }

    /**
     * Display one user review
     * @param user_review $userreview user review
     * @return string HTML of user review
     */
    public function display_review(user_review $userreview) {
        // Display photo and name of author,his rate,time of review and text of review.
        $reviewhtml =
            html_writer::div($this->user_picture($userreview->user, ['size' => 30, 'link' => false]), 'review_usericon').
            html_writer::div($userreview->user->lastname.' '.$userreview->user->firstname, 'review_userfio').
            html_writer::div($this->rate_stars($userreview->rate), 'review_rate', ['id' => 'review_rate'.$userreview->id]).
            html_writer::div(strftime('%e %B %Y', $userreview->timeadded), 'review_date').
            html_writer::div($userreview->text, 'review_text');
        return html_writer::div($reviewhtml, 'display_review');
    }

    /**
     * Display rate with stars
     * @param int $rate rate value
     * @return string HTML of rate
     */
    public function rate_stars($rate) {
        $ratestars = '';
        for ($i = 1; $i <= 5; $i++) {
            // Display empty/not empty star in accordance with rate.
            $cssclass = 'star'.($rate >= $i ? ' star_notempty' : '');
            $ratestars .= html_writer::tag('span', '&nbsp;', ['class' => $cssclass, 'data-rate' => $i]);
        }
        return $ratestars;
    }

    /**
     * Render moderation page
     * @param object|null $review reviewe
     * @param array $auxfilter auxiliary filter
     * @param int $perpage reviews per page
     * @return string HTML of moderation page
     */
    public function moderate_page($review = null, $auxfilter = null, $perpage = 0) {
        user_review::save_status(); // We may need to save status from GET-params (when JS is turned off).
        $head = [];
        $filter = ['notempty_reviews' => 1];
        $content = '';
        if (!$perpage) {
            $perpage = get_config('mod_review', 'perpage_moderate');
        }
        if ($review) { // If received review - show only reviews of one course.
            $content .= $this->heading(get_string('pluginname', 'mod_review'));
            $filter['reviewid'] = $review->id;
        } else { // If review not received - show reviews of all site (could be used in other plugins to display reviews).
            $head['category'] = get_string('category');
            $head['course'] = get_string('course');
        }

        if ($auxfilter) { // Add auxiliary filter (could be used in other plugins to display reviews).
            $filter += $auxfilter;
        }

        $page = optional_param('page', 0, PARAM_INT); // Page param for paging.
        $countentries = user_review::count($filter);
        // Get user reviews with paging.
        $userreviews = user_review::get($filter, $page, $perpage);

        $table = new html_table;  // Initialize a new HTML table.
        $table->attributes['class'] = 'reviewtable';
        $head += [
            get_string('author', 'mod_review'),
            get_string('review', 'mod_review'),
            get_string('rate', 'mod_review'),
            get_string('status', 'mod_review')
        ];
        $table->head = $head;

        if (empty($userreviews)) {   // If no user reviews.
            // Display information.
            $emptycell = new html_table_cell(get_string('entries_notfound', 'mod_review'));
            $emptycell->colspan = count($table->head);
            $table->data[] = new html_table_row([$emptycell]);
        } else {  // If reviews found.
            foreach ($userreviews as $userreview) { // For each review.
                $row = new html_table_row; // Add review info in table.
                // Category & course (if needed), author, text, rate, and status switcher widget.
                $row->cells = ($review)
                    ? []
                    : ['category' => html_writer::link(
                            new moodle_url('/course/management.php',
                            ['categoryid' => $userreview->categoryid]),
                        $userreview->categoryname, ['target' => '_blank']),
                        'course' => html_writer::link(new moodle_url('/course/view.php', ['id' => $userreview->review->course]),
                        $userreview->coursename, ['target' => '_blank'])
                      ];
                $row->cells += [
                    $userreview->user->lastname.' '.$userreview->user->firstname.
                        html_writer::empty_tag('br').
                        html_writer::tag('span', date('d.m.Y H:i:s', $userreview->timeadded), ['class' => 'reviewtable_date']),
                    $userreview->text,
                    $userreview->rate,
                    html_writer::div($this->status_switcher($userreview), 'status_container',
                        ['id' => 'status_container'.$userreview->id])
                ];
                $table->data[] = $row;
            }
        }
        $content .= html_writer::table($table); // Add table to the page.
        $content .= $this->paging_bar($countentries, $page, $perpage, $this->page->url); // Add paging bar.
        return html_writer::div($content, 'page_container');
    }

    /**
     * Display status switcher for moderation
     * @param user_review $userreview user review
     * @return string HTML of widget
     */
    public function status_switcher(user_review $userreview) {
        $html = '';
        if (!$this->page->has_set_url()) { // Check for page url set and if not - set it.
            $cm = get_coursemodule_from_instance('review', $userreview->review->id);
            $this->page->set_url(new moodle_url('/mod/review/view.php', ['id' => $cm->id]));
        }
        // For each statuses.
        foreach ([user_review::REVIEW_RETURNED, user_review::REVIEW_NOTCHECKED, user_review::REVIEW_ACCEPTED] as $status) {
            // Add all statuses as an empty rectangles - to catch click events.
            // Create url to save status - when JS is turned off.
            $url = new moodle_url($this->page->url, ['userreviewid' => $userreview->id, 'newstatus' => $status]);
            $html .= html_writer::link($url, '&nbsp;',
                ['class' => 'status status'.$status, 'data-status' => $status, 'data-reviewid' => $userreview->id]);
        }
        // Add the handler of switcher - to catch drag events.
        $html .= html_writer::link('#', '&nbsp;',
            ['class' => 'status handler selected draggable',
            'data-status' => $userreview->status,
            'data-reviewid' => $userreview->id]);
        return html_writer::div($html, 'status_switcher instatus'.$userreview->status).
            html_writer::div(get_string('status'.$userreview->status.'_short', 'mod_review'), 'textstatus');
    }

}
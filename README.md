# moodle-mod_review

## About

Review plugin allows students to rate the course and to write their reviews of it.

## Features
* user-friendly interface
* could work without JavaScript
* configurable color scheme 
* configurable completion settings
* GDPR support

## Installation

Review plugin is an _activity module_.
Please see Moodle's docs page about [Installing Plugins](https://docs.moodle.org/38/en/Installing_plugins)

## Plugin settings

After the installation in _Site administration -> Plugins -> Activity modules -> Course reviews_ siteadmins could set a color for module interface elements, a number of reviews on module page and a number of reviews on moderation page.

## Adding to the course

To add the module to a course a user with capability to manage elements of the course, need to go to the course page, turn editing on, click on **Add and activity or resource** in the desired section and then select **Course review** in the selection window.

To display a rating widget right on the course page the option **Show rating widget on the course page** should be enabled. Widget allows users to rate the course from the course page. If this option is enabled and the **Description** field is filled in for an element, the description text will also be displayed on the course page.  
Also, in addition to the standard options, you can configure the completion of the element depending on rating or/and review
in the **Activity Completion** box.

## Using of a module

If the rating widget is displayed on the course page, any users enrolled in the course could rate the course on a scale between 1 and 5 by clicking on the corresponding number of stars right on the course page. After that the widget will display their rating value. Users could change their rates an unlimited number of times. 

To write review user needs to go to the module page. On the page there are:
* Rating widget
* Textarea for writing a review
* Other rates and reviews (if there are any accepted)

## Reviews moderation

To moderate the reviews of the course a user with capability to manage reviews on the **Course review page** need to go to _Moderate reviews_ in the settings menu.

Moderation page contains all reviews for the course in a table format.  The table shows the author's name, the time the review was left, the text of the review, the grade in text format, and the current status of the review.
Review status is an interactive switch that allows the moderator to change the review status by clicking or dragging.
All reviews in status **Accepted** will be displayed on the module page in **Other reviews and rates** block.



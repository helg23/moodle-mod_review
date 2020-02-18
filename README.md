# moodle-mod_review

## About

Review plugin allows students to rate the course and to write their reviews on it.

## Features


## Installation

Review plugin is an _activity module_.
Please see Moodle's docs page about [Installing Plugins](https://docs.moodle.org/38/en/Installing_plugins)

## Adding to the course

Under a role with capability to manage elements of the course, you need to go to the course page, turn editing on, click on **Add and activity or resource** in the desired section and then select "Course review" in the selection window.

In addition to the default settings, you can also enable the option **Show rating widget on the course page**. In this case a widget will be displayed on the course page, allowing the user to rate the course from the course page.   If this option is enabled and the **Description** field is filled in for an element, the description text will also be displayed on the course page.  
Also, in addition to the standard options, you can configure the completion of the element depending on rating or/and review
in the **Activity Completion** box.

## Using of a module
If the rating widget is displayed on the course page, any course user can rate the course on a scale between 1 and 5 by clicking on the corresponding number of stars right on the course page. After that the widget will display the new rating value.  

To write review user needs to go to the module page. On the page there are:
* Rating widget
* Textarea for writing a review
* Other reviews and rates (if there are any accepted)

## Reviews moderation

Under a role with capability to manage reviews on the **Course review page** go to _Moderate reviews_ in the settings menu.

Moderation page contains all reviews for the course in a table format.  The table shows the author's name, the time the review was left, the text of the review, the grade in text format, and the current status of the review.
Review status is an interactive switch that allows the moderator to change the review status by clicking or dragging.
All reviews in status **Accepted** will be displayed on the module page in **Other reviews and rates** block.

# wp-qjumpers-jobs

QJumpers Jobs Wordpress Plugin

A plugin to display your QJumpers jobs on your own Wordpress site.

## Installation

- Install the plugin by manually copying `wp-qjumpers-jobs-level.php` to your plugin folder
- Activate the plugin under the plugin page
- Under _Settings > QJumpers Jobs_ enter the following fields:

  - API Key
  - API URL
  - Job Site URL
  - Parent Page (This is the page that will be used to display the individual job listings, see below for more details)

## List of Jobs

- Once you have saved the API settings select the post or page you wish to place your list of jobs on
- Add the following shortcode to the content editor, save and publish and you will now have a list of job listings

  ```php
  [qj_jobs]
  ```

## Single Job Listing

- To complete the link to a single job listing and display, create a new page (this will be the parent page in Qjumper settings) and add the following shortcode to the content editor, save and publish and you will now have a single job listing

  ```php
  [qj_job_listing]
  ```

## Styling

- Last step is to style the QJumpers jobs to your liking as currently no styling is pre-applied

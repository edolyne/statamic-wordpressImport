<?php
// USAGE: php convert.php

// Edit the details below to your needs
$wp_file = 'resources/data/wpexport.xml';
$export_folder = '../../content/collections/wordpress'; // existing files will be over-written, use with care

if (file_exists($wp_file)) {
  $xml = simplexml_load_file($wp_file);
  $count = 0;
  foreach ($xml->channel->item as $item) {
    $count ++;

    print "Exporting: (".$count.") " . $item->title."\n";
    $title = $item->title;
    $tags = array();
    $categories = array();
    $item_date = strtotime($item->pubDate);
    $file_name = $export_folder.date("Y-m-d-Hi", $item_date)."-".$item->children('wp', true)->post_name.".md";

    if ($title == '') {
        $title = 'Untitled post';
    }

    foreach($item->category as $taxonomy) {
        if ($taxonomy['domain'] == 'post_tag') {
            $tags[] = "'".$taxonomy['nicename']."'";
        }
        if ($taxonomy['domain'] == 'category') {
            $categories[] = "'".$taxonomy['nicename']."'";
        }
    }


    // TODO Saving images found in post

    $content = $item->children('content', true)->encoded;
    preg_match_all('/<img[^>]+>/i',$content, $images);

    foreach($images as $image) {
      foreach($image as $src) {
        preg_match('/src="([^"]*)"/i', $src, $file_url);
        //print basename($file_url[1]) . "\n";
        $image_file = 'images/'.basename($file_url[1]);

        $fp = fopen($image_file, 'wb');
        $myfile = file_get_contents($file_url[1]);
        fwrite($fp, $myfile);
        fclose($fp);
        //print $file_url[1];
      }
    }



    print "  -- filename: ".$file_name;

    $markdown  = "---\n";
    $markdown .= "title: '" . $title ."'\n";
    $markdown .= "creator: '" . $item->children('dc', true)->creator ."'\n";
    $markdown .= "post_id: '" . $item->children('wp', true)->post_id ."'\n";
    if (sizeof($tags)) {
      $markdown .= "tags: [".implode(", ", $tags)."]\n";
    }
    if (sizeof($categories)) {
      $markdown .= "categories: [".implode(", ", $categories)."]\n";
    }
    $markdown .= "excerpt: '" . $item->children('excerpt', true)->encoded . "'\n";
    $markdown .= "permalink: '" . $item->link."'\n";
    $markdown .= "comments:\n";
    foreach ($item->children('wp', true)->comment as $comment) {
      $markdown .= "  -\n";
      $markdown .= "    comment_id: '" . $comment->comment_id . "'\n";
      $markdown .= "    comment_author: '" . $comment->comment_author . "'\n";
      $markdown .= "    comment_email: '" . $comment->comment_author_email . "'\n";
      $markdown .= "    comment_content: '". $comment->comment_content . "'\n";
    }
    $markdown .= "---\n";
    $markdown .= $content;
    $markdown .= "\n";

    file_put_contents($file_name, $markdown);

    print "\n";
  }
}

// Credit: http://sourcecookbook.com/en/recipes/8/function-to-slugify-strings-in-php
function slugify($text)
{
    // replace non letter or digits by -
    $text = preg_replace('~[^\\pL\d]+~u', '-', $text);

    // trim
    $text = trim($text, '-');

    // transliterate
    if (function_exists('iconv'))
    {
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    }

    // lowercase
    $text = strtolower($text);

    // remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);

    if (empty($text))
    {
        return 'n-a';
    }

    return $text;
}
<?php

namespace Statamic\Addons\Wordpress\Commands;

use Statamic\Extend\Command;
use Statamic\API\File;
use Statamic\API\Asset;
use Statamic\API\AssetContainer;
use Statamic\API\Entry;

class WordpressCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wordpress:convert';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert Wordpress Posts To Statamic';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $wp_file = 'site/addons/Wordpress/resources/data/wpexport.xml';
        $export_folder = '/content/collections/wordpress'; // existing files will be over-written, use with care
        $collection_name = 'blog';

        if (File::get($wp_file)) {
            $xml = simplexml_load_file($wp_file);
            $count = 0;

            foreach ($xml->channel->item as $item) {

                $count ++;

                if ($item->children('wp', true)->post_type == 'post') {

                    // @Todo
                    // Tags
                    // Categories
                    // Author
                    // Take Care of Memory Leak

                    print "Exporting: (".$count.") " . $item->title."\n";
                    $title = $item->title;
                    $tags = array();
                    $categories = array();
                    $item_date = strtotime($item->pubDate);
                    $file_name = $item->children('wp', true)->post_name;
                    $status = false;

                    if ($item->children('wp', true)->status == 'publish') {
                        $status = true;
                    }

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

                    $content = $item->children('content', true)->encoded;
                    preg_match_all('/<img[^>]+>/i',$content, $images);

                    foreach($images as $image) {
                        foreach($image as $src) {

                            preg_match('/src="([^"]*)"/i', $src, $file_url);

                            $fileName = basename($file_url[1]);
                            $destination = "assets/img/{$fileName}";
                            
                            if (!File::exists($destination)) {

                                $source = $file_url[1];

                                $data = file_get_contents($source);

                                $handle = fopen($destination, "w");
                                fwrite($handle, $data);
                                fclose($handle);
                            }

                            $content = preg_replace('/src="([^"]*)"/i', 'src="/assets/img/'.basename($file_url[1]).'"', $content);
                            $content = preg_replace('/width="([^"]*)"/i', '', $content);
                            $content = preg_replace('/height="([^"]*)"/i', '', $content);
                        }
                    }

                    Entry::create($file_name)
                        ->collection($collection_name)
                        ->with(
                            [
                                'title' => $title,
                                'content' => $content
                            ]
                        )
                        ->published($status)
                        ->date(date("Y-m-d", $item_date))
                        ->get()
                        ->save();

                    print "\n";

                }
            }
        }
    }

    
}

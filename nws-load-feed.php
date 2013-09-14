<?php
/*
  nws-reload-feed : Reload one feed

  This script is part of NWS
  https://github.com/xaccrocheur/nws/

*/

ini_set('display_errors', 'Off');

// If the feed's URL contains one of those, it will be treated as a Photoblog (full img width)
$photoblog_domains = array(".tumblr.", "cabinporn", "bigpicture", "www.xkcd.com");


include('nws-favicon.php');


/**
 * Searches for the first occurence of an html <img> element in a string
 * and extracts the src if it finds it. Returns boolean false if
 * <img> element is not found.
 * @param    string  $str    An HTML string
 * @return   mixed           The contents of the src attribute in the
 *                           found <img> or boolean false if no <img>
 *                           is found
 */
function str_img_src($html) {
    if (stripos($html, '<img') !== false) {
        $imgsrc_regex = '#<\s*img [^\>]*src\s*=\s*(["\'])(.*?)\1#im';
        preg_match($imgsrc_regex, $html, $matches);
        unset($imgsrc_regex);
        unset($html);
        if (is_array($matches) && !empty($matches)) {
            $res = $matches[2];
            /* $res = str_replace("//", "", $matches[2]); */
            /* echo "plop:".$res.")"; */
            return $res;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function reparse($u) {

    global $photoblog_domains;

    foreach ($photoblog_domains as $photoblog_domain) {
        if (strstr($u, $photoblog_domain)) $photoblog = true;
    }

    $limit="18";
    $feedRss=simplexml_load_file($u);
    $i=0;
    $url = parse_url($u);
    $subs = explode( '.', $url['host']);
    $domain = $subs[0].'.'.$subs[count($subs) -2].'.'.$subs[count($subs) -1];

    /* var_dump($subs); */

    $favicon = getFavicon('http://'.$domain);
    /* $favicon = "img/nws.png"; */

    if($feedRss) {
        if (isset($feedRss->channel->item)) {
            $items = $feedRss->channel->item;
            $feedTitle = $feedRss->channel->title;
        }
        else {
            if (isset($feedRss->item)) {
                $items = $feedRss->item;         // rss of some sort
            } elseif (isset($tumb)) {	    	 // tumblr
                $items = $feedRss->posts->post;
                $feedTitle = $tumb;
            } else {		         	         // Atom
                $items = $feedRss->entry;
                $feedTitle = $feedRss->title;
            }
        }

        echo '
             <div class="feed" title ="'.$u.'">
                 <div class="feedTitle">
                     <span class="favicon"><img src="'.$favicon.'" /></span> <a href="'.$u.'" title=""></span>'.$feedTitle.'</a>
                 </div>
                 <ul>';

        foreach($items as $item) {
            if ($i++ < $limit) {
                $link = htmlspecialchars($item->link);
                $title = strip_tags($item->title);
                $imgSrc = str_img_src($item->description);

                // Image
                if (isset($imgSrc) || $imgSrc == "")
                    list($width, $height) = getimagesize($imgSrc);

                $atomImg = $item->enclosure['url'];
                $elseSrc = str_img_src(strip_tags($item->content, "<img>"));
                $elseSrx = htmlspecialchars_decode($item->description);

                // Check if relative path
                if (!CheckImageExists("http://".str_replace("//", "", $elseSrc)))
                    $elseSrc = $domain.$elseSrc;
                /* $res = str_replace("//", "", $matches[2]); */

                //Use that namespace
                $namespaces = $item->getNameSpaces(true);

                //Relative
                if ($item->children($namespaces['media']))
                    $media = $item->children($namespaces['media']);

                if (isset($media))
                    $mediaImg = $media->thumbnail->attributes()->url;

                if ($photoblog || $title == "Photo") {
                    $img = '<a href="'.$imgSrc.'"><img class="full" title="'.$title.'" alt="'.$title.'" src="'.$imgSrc.'" /></a>';
                    $title = $title;
                } elseif (!empty($atomImg)) {
                    $ext = pathinfo($atomImg, PATHINFO_EXTENSION);
                    if ($ext == "mp3") {
                        $img = '<a href="'.$atomImg.'"><img class="feed audio" alt="Audio content" src="img/snd.png" /></a>';
                    } else {
                        $img = '<a href="'.$atomImg.'"><img class="feed" alt="'.$ext.' - atomImg" src="'.$atomImg.'" /></a>';
                    }
                } elseif (!empty($mediaImg)) {
                    $img = '<a href="'.$mediaImg.'"><img class="feed" alt="media" src="'.$mediaImg.'" /></a>';
                } elseif (!empty($imgSrc) && $width > 2 && $title != "Photo") {
                    $img = '<a href="'.$imgSrc.'"><img class="feed" alt="regexp" src="'.str_replace("http://www.", "http://", $imgSrc).'" /></a>';
                } elseif (!empty($elseSrc)) {
                    $img = '<a href="'.$elseSrc.'"><img class="feed" alt="else" src="http://'.$elseSrc.'" /></a>';
                    $description = $item->content;
                } else {
                    $img = '';
                }

                if (empty($link)) $link = htmlspecialchars($item->link['href']);
                $fullDescription = strip_tags($item->description, "<img>, <p>");

                $description = (isset($item->description) ? $item->description : $item->content);
                $description = htmlspecialchars(htmlspecialchars_decode(trim(htmlspecialchars(strip_tags($description)))), ENT_NOQUOTES);

                $description = htmlspecialchars_decode($description);

                echo '
                          <li title="'.$description.'">
                              <div class="all">'.$img.'<a target="_blank" href="'.$link.'">'.$title.'</a>
                                  <hr />
                              </div>
                          </li>';
            }
        }
        echo '
                      </ul>
                      </div>';
    }
}

reparse($_GET['z']);

/* echo "plop"; */

?>
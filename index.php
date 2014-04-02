<?php

/**
 * Returns an array with the following values:
 *
 * order_active_key     - The currently active order key
 * reverse_direction    - A boolean indicating whether the order should be
 *                        in reversed direction.
 * order_params         - The parameters that should be used for links
 *                      - to change the order of a certain order var.
 */
function get_order($order_keys, $reverse_order_keys) {

    $order_active_key = '';
    $order_params = array_combine($order_keys, $order_keys);

    if ($_GET['order']) {
        $order_active_param = $_GET['order'];
    }

    if (substr($order_active_param, 0, 1) == '-') {
        $reverse_direction = true;
        $order_active_key = substr($order_active_param, 1);
    } else {
        $reverse_direction = false;
        $order_active_key = $order_active_param;
    }

    if (!in_array($order_active_key, $order_params)) {
        $order_active_key = 'name';
    }

    if (!$reverse_direction) {
        $order_params[$order_active_key] = '-' . $order_active_key;
    }

    if (in_array($order_active_key, $reverse_order_keys)) {
        $reverse_direction = !$reverse_direction;
    }

    return [
        $order_active_key,
        $order_active_param,
        $reverse_direction,
        $order_params
    ];

}

/**
 * Returns the files in the given `$dir`.
 *
 * Will return an array of file arrays. A file array will contain the
 * following keys:
 *
 * is_dir   - A boolean indicating whether this file is a dir
 * name     - The name for the file
 * date     - The last modification date for the file
 * size     - The filesize
 */
function get_files($dir) {

    $files = [];
    $filenames = glob($dir . '/*', GLOB_MARK);

    foreach ($filenames as $filepath) {

        $is_dir = is_dir($filepath);
        $filename = substr($filepath, strlen($dir) + 1);

        if ($dir == '.' && $filename == basename($_SERVER['PHP_SELF'])) {
            continue;
        }

        $files[] = [
            'is_dir' => $is_dir,
            'name' => $filename,
            'date' => filemtime($filepath),
            'size' => filesize($filepath)
        ];

    }

    return $files;

}

/**
 * Order the given `$files` array.
 *
 * @param `$order_active_key` - The key that will be used for ordering
 * @param `$reverse_direction` - A boolean indicating whether to order
 *                               in reverse direction.
 */
function order_files($files, $order_active_key, $reverse_direction) {

    usort(
        $files,
        function($a, $b) use ($order_active_key, $reverse_direction) {

            if ($reverse_direction) {
                list($a, $b) = [$b, $a];
            }

            if($order_active_key == 'name') {
                return strcasecmp($a[$order_active_key], $b[$order_active_key]);
            } else {
                return $a[$order_active_key] - $b[$order_active_key];
            }

        }
    );

    return $files;

}

/**
 * Will reorder the given `$files` array so that directories will end up
 * above normal files.
 */
function order_dirs_on_top($files) {

    $dirs = [];
    $non_dirs = [];

    foreach ($files as $file) {
        if($file['is_dir']) {
            $dirs[] = $file;
        } else {
            $non_dirs[] = $file;
        }
    }

    return array_merge($dirs, $non_dirs);

}

/**
 * Makes a float human readable.
 *
 * Will round the float to the given `$precision` and will zero-fill to
 * the given `$precision`.
 */
function human_readable_float($float, $precision = 2) {
    return number_format(
        round($float, $precision),
        $precision
    );
}

/**
 * Convert the given `$bytes` to a human readable string.
 *
 * Will express the bytes in forms as byte, kilobyte, megabyte etc. and
 * add an appropriate suffix for this unit.
 * Will round to the given `$precision`.
 */
function bytes_to_human_readable($bytes, $precision = 2) {

    $kilobyte = 1024;
    $megabyte = $kilobyte * 1024;
    $gigabyte = $megabyte * 1024;
    $terabyte = $gigabyte * 1024;
   
    if (($bytes >= 0) && ($bytes < $kilobyte)) {
        return human_readable_float($bytes, $precision) . ' B';
    } elseif (($bytes >= $kilobyte) && ($bytes < $megabyte)) {
        return human_readable_float($bytes / $kilobyte, $precision) . ' KB';
    } elseif (($bytes >= $megabyte) && ($bytes < $gigabyte)) {
        return human_readable_float($bytes / $megabyte, $precision) . ' MB';
    } elseif (($bytes >= $gigabyte) && ($bytes < $terabyte)) {
        return human_readable_float($bytes / $gigabyte, $precision) . ' GB';
    } elseif ($bytes >= $terabyte) {
        return human_readable_float($bytes / $terabyte, $precision) . ' TB';
    } else {
        return human_readable_float($bytes, $precision) . ' B';
    }

}

/**
 * Returns the order css classes for the given `$order_keys`, based on
 * the given `$order_active_key` and `$reverse_direction`.
 *
 * This will be an array with in the keys the order keys and in the
 * values the class.
 */
function get_order_css_classes($order_keys, $order_active_key, $reverse_direction) {

    $order_css_classes = [];

    foreach ($order_keys as $order_key) {
        $order_css_classes[$order_key] = (
            $order_key == $order_active_key ?
            'order-' . ($reverse_direction ? 'desc' : 'asc') : ''
        );
    }

    return $order_css_classes;

}

/**
 * Returns the path to the directory that should be listed.
 *
 * Returns an array with the directory names in the values.
 */
function get_dir_path_arr() {

    if ($_GET['dir']) {
        $dir_path_arr = explode('/', $_GET['dir']);
    } else {
        $dir_path_arr = ['.'];
    }

    // Ignore dirs which name is two dots, because that will go a dir
    // higher. Very important for security!
    foreach ($dir_path_arr as $key => $dirname) {
        if ($dirname == '..') {
            unset($dir_path_arr[$key]);
        }
    }

    if (!end($dir_path_arr)) {
        array_pop($dir_path_arr);
    }

    return $dir_path_arr;

}

/**
 * Returns the path to the parent dir or `false` if there is no parent
 * dir.
 */
function get_parent_dir_path($dir_path_arr) {

    $parent_dir_path = $dir_path_arr;
    array_pop($parent_dir_path);

    if (count($parent_dir_path) > 0) {
        return implode('/', $parent_dir_path);
    } else {
        return false;
    }

}

/**
 * Returns the name of the directory this file is located.
 */
function get_base() {
    return preg_replace(':.*/:', '', dirname($_SERVER['PHP_SELF']));
}

/**
 * Returns the name of the directory to the path in `$dir_path_arr`.
 */
function get_dirname($dir_path_arr) {

    if (count($dir_path_arr) > 1) {
        $subdirs = implode('/', array_slice($dir_path_arr, 1)) . '/';
    } else {
        $subdirs = '';
    }

    return get_base() . '/' . $subdirs;

}

// the keys on the files array that can be used for ordering
$order_keys = ['name', 'size', 'date'];

// Reverse the order for these keys by default.
//
// In case of the size, the largest file is usually the most
// interesting.
// In case of the date, the latest file is usually the most
// interesting.
$reverse_order_keys = ['size', 'date'];

$dir_path_arr = get_dir_path_arr();
$dir_path = implode('/', $dir_path_arr);
$parent_dir_path = get_parent_dir_path($dir_path_arr);
$dirname = get_dirname($dir_path_arr);

list(
    $order_active_key,
    $order_active_param,
    $order_reverse_direction,
    $order_params
) = get_order($order_keys, $reverse_order_keys);

$files = order_dirs_on_top(order_files(
    get_files($dir_path),
    $order_active_key,
    $order_reverse_direction
));

$order_css_classes = get_order_css_classes(
    $order_keys,
    $order_active_key,
    $order_reverse_direction
);

$css_border_radius = '7px';
$css_horizontal_padding = '10px';

?>
<!DOCTYPE html>
<html>

    <head>

        <meta charset="utf-8">
        <title><?=$dirname?></title>
        <meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0">

        <style type="text/css">

            html, body {
                font-family: Verdana, sans-serif;
                font-size: 15px;
                margin: 0;
                padding: 0.5%;
            }

            a {
                color: #000;
                text-decoration: none;
            }

            h1 {
                padding: 0 0 10px;
                margin: 0;
            }

            table {
                width: 100%;
                border-spacing: 0;
                border: solid 1px #AAA;
                border-width: 1px 0 1px 1px;
                border-radius: <?=$css_border_radius?>;
            }

            table th,
            table td {
                cursor: pointer;
                border-right: solid 1px #AAA;
            }

            table tr:first-child th:first-child {
                border-top-left-radius: <?=$css_border_radius?>;
            }

            table tr:first-child th:last-child {
                border-top-right-radius: <?=$css_border_radius?>;
            }

            table tr:last-child td:first-child {
                border-bottom-left-radius: <?=$css_border_radius?>;
            }

            table tr:last-child td:last-child {
                border-bottom-right-radius: <?=$css_border_radius?>;
            }

            table thead th {
                padding: 10px <?=$css_horizontal_padding?>;
                text-align: left;
                font-weight: bold;
                background: #CCC;
            }

            table thead th:hover,
            table thead th:active {
                background: #DDD;
            }

            table thead .order-asc .order-icon,
            table thead .order-desc .order-icon {
                float: right;
                width: 16px;
                height: 16px;
                background: url('data:image/gif;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABmJLR0QA/wD/AP+gvaeTAAAANUlEQVQ4jWNgGAWEQDkUkwUaGBgY/kNxAyWaSTYEm2aiDcGnmaAhxGjGaQgpmskO2FGAAwAAx7QwxeVl/3sAAAAASUVORK5CYII=');
            }

            table thead .order-asc .order-icon {
            }

            table thead .order-desc .order-icon {
                transform: rotate(180deg);
            }

            table tbody tr:nth-child(2n) td {
                background-color: #FAFAFA;
            }

            table tbody tr:nth-child(2n + 1) td {
                background-color: #F0F0F0;
            }

            table tbody tr:hover td,
            table tbody tr:active td {
                background-color: #DDD;
            }

            table tbody td {
                padding: 8px <?=$css_horizontal_padding?>;
            }

        </style>

    </head>

    <body>

        <h1><?=$dirname?></h1>

        <table>

            <thead>
                <tr>
                    <th class="name <?=$order_css_classes['name']?>">
                        <a href="?dir=<?=rawurlencode($dir_path)?>&order=<?=$order_params['name']?>">Name</a>
                        <div class="order-icon"></div>
                    </th>
                    <th class="date <?=$order_css_classes['date']?>">
                        <a href="?dir=<?=rawurlencode($dir_path)?>&order=<?=$order_params['date']?>">Date</a>
                        <div class="order-icon"></div>
                    </th>
                    <th class="size <?=$order_css_classes['size']?>">
                        <a href="?dir=<?=rawurlencode($dir_path)?>&order=<?=$order_params['size']?>">Size</a>
                        <div class="order-icon"></div>
                    </th>
                </tr>
            </thead>

            <tbody>
                <?

                if ($parent_dir_path) {
                    ?>
                    <tr>
                        <td class="name">
                            <a href="?dir=<?=rawurlencode($parent_dir_path)?>&order=<?=$order_active_param?>">../</a>
                        </td>
                        <td class="date">--</td>
                        <td class="size">--</td>
                    </tr>
                    <?
                }

                foreach ($files as $file) {
                    ?>
                    <tr>
                        <td class="name">
                            <?
                            if ($file['is_dir']) {
                                ?>
                                <a href="?dir=<?=rawurlencode($dir_path . '/' . $file['name'])?>&order=<?=$order_active_param?>"><?=$file['name']?></a>
                                <?
                            } else {
                                ?>
                                <a href="<?=$dir_path . '/' . $file['name']?>"><?=$file['name']?></a>
                                <?
                            }
                            ?>
                        </td>
                        <td class="date">
                            <?=date("d F Y H:i", $file['date'])?>
                        </td>
                        <td class="size">
                            <?
                            if ($file['is_dir']) {
                                echo '--';
                            } else {
                                echo bytes_to_human_readable($file['size']);
                            }
                            ?>
                        </td>
                    </tr>
                    <?
                }
                ?>
            </tbody>

        </table>

        <script>

            /**
             * Will look for the first parentNode of `click_source`
             * which has a tagName that's equal to
             * `parent_element_tagname` and will open the first link in
             * this node.
             */
            function open_first_link(click_source, parent_element_tagname) {

                var el = click_source;

                while (el.tagName != parent_element_tagname) {
                    el = el.parentNode;
                }

                window.location = el.querySelector('a').getAttribute('href');

            }

            document.querySelector('table tbody').addEventListener('click', function(event) {
                open_first_link(event.target, 'TR');
            });

            document.querySelector('table thead').addEventListener('click', function(event) {
                open_first_link(event.target, 'TH');
            });

        </script>

    </body>

</html>

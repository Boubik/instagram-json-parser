<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Instagram parser</title>
</head>

<body>
    <?php
    require "functions.php";
    $fileList = glob('data/*.zip');
    $oldfiles = glob("data/extracted/*");
    $zip = new ZipArchive;
    foreach ($fileList as $filename) {
        $res = $zip->open($filename);
        if ($res === TRUE) {
            if (!(in_array("data/extracted/" . mb_substr($filename, 5, strlen($filename) - 9), $oldfiles))) {
                $zip->extractTo('data/extracted/' . mb_substr($filename, 5, strlen($filename) - 9));
                $zip->close();
                //echo "rozbaleno<br>\n";
            } else {
                //echo "soubor už byl rozbalen<br>\n";
            }
        } else {
            //echo "zip nenalezen<br>\n";
        }
    }

    $fileList = glob('data/extracted/*');
    foreach ($fileList as $filename) {
        $f_jsons = glob($filename . '/*.json');
        foreach ($f_jsons as $f_json) {
            //$f_json = $f_jsons[1];
            $handle = load_file($f_json, "r");
            if ($handle != "{}") {
                if (preg_match("/\/[A-Za-z0-9]*\.json/", $f_json, $resault)) {
                    echo "<a href=\"index.php?category=" . mb_substr($resault[0], 1, strlen($resault[0]) - 6) . "\">" . mb_substr($resault[0], 1, strlen($resault[0]) - 6) . "</a><br>";
                }
            }
        }
    }
    if (isset($_GET["category"])) {
        foreach ($fileList as $filename) {
            $f_jsons = glob($filename . '/*.json');
            $user = mb_substr($filename, 15);
            $user = explode("_", $user);
            $user = $user[0];
            foreach ($f_jsons as $f_json) {
                if (preg_match("/\/[A-Za-z0-9]*\.json/", $f_json, $resault) and mb_substr($resault[0], 1, strlen($resault[0]) - 6) == $_GET["category"]) {
                    echo "<br><br>i'm in " . mb_substr($resault[0], 1, strlen($resault[0]) - 6) . "<br>";
                    $handle = load_file($f_json, "r");
                    echo "<br>";
                    $j_array = json_decode($handle, true);

                    $peoples = array();
                    switch ($_GET["category"]) {
                        case "messages":
                            echo "<a href=\"index.php?category=messages\">zpět</a>";
                            echo "<br><br>";
                            if (isset($_GET["show"])) {
                                foreach ($j_array as $name => $item) {
                                    $par = explode(" ", $_GET["show"]);
                                    $look = true;
                                    foreach ($item["participants"] as $value) {
                                        if (!(in_array($value, $par))) {
                                            $look = false;
                                            $break;
                                        }
                                    }
                                    if ($look) {
                                        echo "<b>zpravy od: " . str_replace(" ", ", ", $_GET["show"]) . "</b>";
                                        echo "<br><br>";
                                        foreach ($item["conversation"] as $mes) {
                                            $time = explode("T", $mes["created_at"]);

                                            $date = explode("-", $time[0]);
                                            $date = $date[2] . "." . $date[1] . " " . $date[0];

                                            $time = explode(":", $time[1]);
                                            $time = $time[0] . ":" . $time[1] . ":" . substr($time[2], 0, 2);

                                            $mes["created_at"] = $time . " - " . $date;

                                            echo "<b>" . $mes["sender"] . "</b> " . $mes["created_at"] . ": " . $mes["text"];
                                            echo "<br>";
                                        }
                                    }
                                }
                            } else {
                                //zpracování zpráv
                                $messages = array();
                                foreach ($j_array as $name => $item) {
                                    $in = false;
                                    foreach ($item["participants"] as $par) {
                                        if (!(in_array($par, $peoples)) or $par == $user) {
                                            $in = true;
                                        } else {
                                            $in = false;
                                            break;
                                        }
                                    }
                                    if ($in) {
                                        $messages[$name] = array();
                                        foreach ($item["participants"] as $key => $par) {
                                            if (!(in_array($par, $peoples))) {
                                                $peoples[] = $par;
                                            }
                                            $messages[$name]["participants"][] = $par;
                                        }
                                        $count = array();
                                        $messages[$name]["messages"]["all"] = count($item["conversation"]);
                                        foreach ($item["conversation"] as $idk) {
                                            if (isset($count[$idk["sender"]])) {
                                                $count[$idk["sender"]] += 1;
                                            } else {
                                                $count[$idk["sender"]] = 1;
                                            }
                                        }
                                        foreach ($count as $key => $idk) {
                                            $messages[$name]["messages"][$key] = $idk;
                                        }
                                    }
                                }
                                //srovnání podle počtu zpráv
                                usort($messages, function ($first, $second) {
                                    return $first["messages"]["all"] < $second["messages"]["all"];
                                });
                                //vypsání zpráv
                                foreach ($messages as $k => $item) {
                                    echo "účastníci zpráv: ";
                                    foreach ($item["participants"] as $key => $par) {
                                        if ($key == 0) {
                                            echo $par . " ";
                                            $href = $par;
                                        } else {
                                            echo ", " . $par . " ";
                                            $href .= " " . $par;
                                        }
                                    }
                                    echo "<br>";
                                    foreach ($item["messages"] as $key => $mes) {
                                        if ($key == "all") {
                                            echo "celkový počet žpráv: " . $mes;
                                            echo "<br>";
                                        } else {
                                            echo "zpráv od " . $key . ": " . $mes;
                                            echo "<br>";
                                        }
                                    }
                                    echo "<a href=\"index.php?category=messages&show=" . $href . "\">zobrazit zprávy</a>";
                                    echo "<br>";
                                    echo "<br>";
                                }
                            }
                            break;
                        default:
                            foreach ($j_array as $item) {
                                print_r($item);
                                echo "<br><br>";
                            }
                            break;
                    }
                }
            }
        }
    }
    ?>
</body>

</html>
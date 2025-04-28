<?php
//require_once "scad.php";

$_doorsSpace = 4;
$_parts = [];
$_scad = [];
$_scad2 = [];
$_scadCuts = [];
$_thick = 16;
$_color = "9F722F";
$_edgeThick = 0.4;
$_precision = 0.5;

function setColor($value) {
    global $_color;
    $_color = $value;
}

function setPrecision($value) {
    global $_precision;
    $_precision = $value;
}

function precision() {
    global $_precision;
    return $_precision;
}
function setEdgeThick($value) {
    global $_edgeThick;
    $_edgeThick = $value;
}

function edgeThick() {
    global $_edgeThick;
    return $_edgeThick;
}
function setThick($value) {
    global $_thick;
    $_thick = $value;
}

function thick() {
    global $_thick;
    return $_thick;
}

function setDoorsSpace($value) {
    global $_doorsSpace;
    $_doorsSpace = $value;
}

function doorsSpace() {
    global $_doorsSpace;
    return $_doorsSpace;
}


function box($horizontalSize, $verticalSize, $depthSize, $horizontalPos = 0, $verticalPos = 0, $depthPos = 0): array {
    global $_color, $_thick, $_edgeThick;
    return [
            "size" => [
                "h" => $horizontalSize, 
                "v" => $verticalSize, 
                "d" => $depthSize,
            ],
            "position" => [
                "h" => $horizontalPos, 
                "v" => $verticalPos, 
                "d" => $depthPos,
            ],
            "closed" => [
                "l" => false,
                "r" => false,
                "u" => false,
                "d" => false,
                "b" => false,
                "f" => false,
            ],
            "color" => $_color,
            "thick" => $_thick,
            "edge-thick" => $_edgeThick,
            "type" => "base",
        ];
}

function set_thick($box, $thick) {
    $box["thick"] = $thick;
    return $box;
}

function set_edge_thick($box, $thick) {
    $box["edge-thick"] = $thick;
    return $box;
}

function set_color($box, $color) {
    $box["color"] = $color;
    return $box;
}


function _parse_value($max, $value) {
    if (strpos($value, "%") !== FALSE) {
        $value = str_replace("%", "", $value);
        return (doubleval($value) / 100.0) * $max;
    }
    
    return doubleval($value);
}

function padding($box, $padValues, $multiplier = 1, $resetClosing = true) {

    foreach(explode(",", $padValues) as $pad) {
        $parts = explode("=", $pad);
        $key = trim($parts[0]);

        if ($key == "rev") {
            $multiplier *= -1;
        }

        if (count($parts) > 1) {
            $valstr = trim($parts[1]);
            $value = doubleval(trim($parts[1]));
            
            if ($key == "m") {
                $multiplier = $value;
            } else if ($key == "left") {
                $v = _parse_value($box["size"]["h"], $valstr) * $multiplier;
                $box["position"]["h"] += $v;
                $box["size"]["h"] -= $v;
                if ($resetClosing) {
                    $box["closed"]["l"] = false;
                }
            } else if ($key == "right") {
                $v = _parse_value($box["size"]["h"], $valstr) * $multiplier;
                $box["size"]["h"] -= $v;
                if ($resetClosing) {
                    $box["closed"]["r"] = false;
                }
            } else if ($key == "bottom") {
                $v = _parse_value($box["size"]["v"], $valstr) * $multiplier;
                $box["position"]["v"] += $v;
                $box["size"]["v"] -= $v;
                if ($resetClosing) {
                    $box["closed"]["d"] = false;
                }
            } else if ($key == "top") {
                $v = _parse_value($box["size"]["v"], $valstr) * $multiplier;
                $box["size"]["v"] -= $v;
                if ($resetClosing) {
                    $box["closed"]["u"] = false;
                }
            } else if ($key == "back") {
                $v = _parse_value($box["size"]["d"], $valstr) * $multiplier;
                $box["position"]["d"] += $v;
                $box["size"]["d"] -= $v;
                if ($resetClosing) {
                    $box["closed"]["b"] = false;
                }
            } else if ($key == "front") {
                $v = _parse_value($box["size"]["d"], $valstr) * $multiplier;
                $box["size"]["d"] -= $v;
                if ($resetClosing) {
                    $box["closed"]["f"] = false;
                }
            }
        }
    }

    return $box;
}

function split_horizontal($box, $sizes, $betweenSpace = 0) {
    return split($box, "horizontal", $sizes, $betweenSpace);
}

function split_vertical($box, $sizes, $betweenSpace = 0) {
    return split($box, "vertical", $sizes, $betweenSpace);
}

function split_depth($box, $sizes, $betweenSpace = 0) {
    return split($box, "depth", $sizes, $betweenSpace);
}

function split($box, $direction, $sizes, $betweenSpace = 0) {
    $max = 0;
    $modifier = "";
    switch ($direction)
    {
        case "vertical":
            $modifier = "v";
            break;
        case "horizontal":
            $modifier = "h";
            break;
        case "depth":
            $modifier = "d";
            break;
    }
    $max = $box["size"][$modifier];
    $sz = explode(",", $sizes);
    $count = count($sz);
    $fixedSize = 0;
    $floatCount = 0;
    $fixedCount = 0;
    foreach ($sz as $size) {
        $size = trim($size);
        if (strlen($size) == 0 || $size == "*") {
            $floatCount++;
        } else {
            $value = _parse_value($max, $size);
            $fixedSize += $value + $betweenSpace;
            $fixedCount++;
        }
    }

    $floatSpace = ($max - $fixedSize - $betweenSpace * ($floatCount - 1)) / $floatCount;

    $result = [];
    $n = 0;
    foreach ($sz as $size) {
        $partSize = 0;
        $size = trim($size);
        if (strlen($size) == 0 || $size == "*") {
            $partSize = $floatSpace;
        } else {
            $partSize = _parse_value($max, $size);
        }

        $n++;

        $newBox = $box;
        $newBox["type"] = "part";
        $newBox["size"][$modifier] = $partSize;
        switch ($direction)
        {
            case "vertical":
                if ($n < $count) {
                    $newBox["closed"]["u"] = false;
                }
                if ($n > 1) {
                    $newBox["closed"]["d"] = false;
                }
                break;
            case "horizontal":
                if ($n < $count) {
                    $newBox["closed"]["r"] = false;
                }
                if ($n > 1) {
                    $newBox["closed"]["l"] = false;
                }
                
                break;
            case "depth":
                if ($n < $count) {
                    $newBox["closed"]["f"] = false;
                }
                if ($n > 1) {
                    $newBox["closed"]["b"] = false;
                }
                break;
        }
        $result[] = $newBox;
        $box["position"][$modifier] += $partSize;

        

        //echo "$betweenSpace ,$n,$count\n";
        if ($betweenSpace > 0 && $n < $count) {
            $newBox = $box;
            $newBox["type"] = "between";
            $newBox["size"][$modifier] = $betweenSpace;
            $result[] = $newBox;
            $box["position"][$modifier] += $betweenSpace;
        }
    }
    return $result;
}

function _add_part($width, $height, $thick, $rounds, $edges, $name) {
    global $_parts;
    $_parts[] = [
        "width" => $width,
        "height" => $height,
        "thick" => $thick,
        "rounds" => $rounds,
        "edges" => $edges,
        "name" => $name,
    ];
}

function _add_scad($code, $name, $layer2 = false) {
    global $_scad, $_scad2;
    if ($layer2) {
        array_unshift($_scad2, "//$name\r\n" .$code);
    } else {
        $_scad[] = "//$name\r\n" .$code;
    }
}

function color_to_scad($hexColor) {
    // Remove '#' if present
    $hexColor = ltrim($hexColor, '#');

    // Split the hex color string into its respective R, G, and B components
    if (strlen($hexColor) == 6) {
        $redHex = substr($hexColor, 0, 2);
        $greenHex = substr($hexColor, 2, 2);
        $blueHex = substr($hexColor, 4, 2);
    } elseif (strlen($hexColor) == 3) {
        // Handle shorthand hex color format like "FFF"
        $redHex = str_repeat($hexColor[0], 2);
        $greenHex = str_repeat($hexColor[1], 2);
        $blueHex = str_repeat($hexColor[2], 2);
    } else {
        return null; // Invalid color format
    }

    // Convert hex to decimal and then to float in range 0-1
    $red   = hexdec($redHex) / 255.0;
    $green = hexdec($greenHex) / 255.0;
    $blue  = hexdec($blueHex) / 255.0;

    return "$red, $green, $blue";
}

function draw_box($box, $color = "8df") {
    $h = $box["size"]["h"];
    $v = $box["size"]["v"];
    $d = $box["size"]["d"];
    $dic = 10;
    $dis = 20;
    _add_scad("translate([" . $box["position"]["h"] . ", " . (-$box["position"]["d"] - $box["size"]["d"]) . ", " . $box["position"]["v"] . "]) { color([" . color_to_scad($color) . "]) {" . 
    "sphere(d = $dis, \$fn=(\$preview ? 32 : 256)); translate([$h, 0, 0]) sphere(d = $dis, \$fn=(\$preview ? 32 : 256));" .
    "translate([0, $d, 0]) sphere(d = $dis, \$fn=(\$preview ? 32 : 256)); translate([$h, $d, 0]) sphere(d = $dis, \$fn=(\$preview ? 32 : 256));" .
    "translate([0, 0, $v]) sphere(d = $dis, \$fn=(\$preview ? 32 : 256)); translate([$h, 0, $v]) sphere(d = $dis, \$fn=(\$preview ? 32 : 256));" .
    "translate([0, $d, $v]) sphere(d = $dis, \$fn=(\$preview ? 32 : 256)); translate([$h, $d, $v]) sphere(d = $dis, \$fn=(\$preview ? 32 : 256));" .

    "translate([0, 0, 0]) rotate([0, 90, 0]) cylinder(h = $h, d = $dic, \$fn=(\$preview ? 32 : 256)); " .
    "translate([0, $d, 0]) rotate([0, 90, 0]) cylinder(h = $h, d = $dic, \$fn=(\$preview ? 32 : 256)); " .
    "translate([0, 0, $v]) rotate([0, 90, 0]) cylinder(h = $h, d = $dic, \$fn=(\$preview ? 32 : 256)); " .
    "translate([0, $d, $v]) rotate([0, 90, 0]) cylinder(h = $h, d = $dic, \$fn=(\$preview ? 32 : 256)); " .

    "translate([0, 0, 0]) rotate([-90, 0, 0]) cylinder(h = $d, d = $dic, \$fn=(\$preview ? 32 : 256)); " .
    "translate([$h, 0, 0]) rotate([-90, 0, 0]) cylinder(h = $d, d = $dic, \$fn=(\$preview ? 32 : 256)); " .
    "translate([0, 0, $v]) rotate([-90, 0, 0]) cylinder(h = $d, d = $dic, \$fn=(\$preview ? 32 : 256)); " .
    "translate([$h, 0, $v]) rotate([-90, 0, 0]) cylinder(h = $d, d = $dic, \$fn=(\$preview ? 32 : 256)); " .

    "translate([0, 0, 0]) rotate([0, 0, 0]) cylinder(h = $v, d = $dic, \$fn=(\$preview ? 32 : 256)); " .
    "translate([$h, 0, 0]) rotate([0, 0, 0]) cylinder(h = $v, d = $dic, \$fn=(\$preview ? 32 : 256)); " .
    "translate([0, $d, 0]) rotate([0, 0, 0]) cylinder(h = $v, d = $dic, \$fn=(\$preview ? 32 : 256)); " .
    "translate([$h, $d, 0]) rotate([0, 0, 0]) cylinder(h = $v, d = $dic, \$fn=(\$preview ? 32 : 256)); " .
    "} color([" . color_to_scad($color) . ", 0.5]) cube([$h, $d, $v]); }", "viewbox", true);
}


function _scad_create($width, $height, $thick, $rounds, $edges, $color, $opacity = 1) {
    $b = 0;
    $h = 0;
    $l = 0;
    $w = 0;

    if ($edges["d"] == 0) {
        $b += 1;
        $h += 1;
    }
    if ($edges["u"] == 0) {
        $h += 1;
    }
    if ($edges["l"] == 0) {
        $l += 1;
        $w += 1;
    }
    if ($edges["r"] == 0) {
        $w += 1;
    }
    $roundCuts = "";

    if ($rounds["ld"] > 0) {
        $radius = $rounds["ld"];
        $cylinder = "translate([$radius, 0, $radius]) rotate([-90, 0, 0]) cylinder(h = $thick + 0.02, d = $radius * 2, \$fn = (\$preview ? 32 : 256));";
        $roundCuts .= " translate([-0.01, -0.01, -0.01]) difference() { cube([$radius, $thick + 0.02, $radius]); $cylinder }";
    }
    if ($rounds["rd"] > 0) {
        $radius = $rounds["rd"];
        $cylinder = "translate([0, 0, $radius]) rotate([-90, 0, 0]) cylinder(h = $thick + 0.02, d = $radius * 2, \$fn = (\$preview ? 32 : 256));";
        $roundCuts .= " translate([$width - $radius + 0.01, -0.01, -0.01]) difference() { cube([$radius, $thick + 0.02, $radius]); $cylinder }";
    }
    if ($rounds["ru"] > 0) {
        $radius = $rounds["ru"];
        $cylinder = "translate([0, 0, 0]) rotate([-90, 0, 0]) cylinder(h = $thick + 0.02, d = $radius * 2, \$fn = (\$preview ? 32 : 256));";
        $roundCuts .= " translate([$width - $radius + 0.01, -0.01, $height - $radius + 0.01]) difference() { cube([$radius, $thick + 0.02, $radius]); $cylinder }";
    }
    if ($rounds["lu"] > 0) {
        $radius = $rounds["lu"];
        $cylinder = "translate([$radius, 0, 0]) rotate([-90, 0, 0]) cylinder(h = $thick + 0.02, d = $radius * 2, \$fn = (\$preview ? 32 : 256));";
        $roundCuts .= " translate([-0.01, -0.01, $height - $radius + 0.01]) difference() { cube([$radius, $thick + 0.02, $radius]); $cylinder }";
    }

    return "union() { translate([0.0$l * 2, 0, 0.0$b * 2]) { color([" . color_to_scad($color) . ", $opacity]) difference() { cube([$width - 0.0$w * 2, $thick, $height - 0.0$h * 2]); $roundCuts } }  { color([1,0,0]) { " . ($edges["d"] > 0 ? "" : "cube([$width - 0.01, $thick, 0.01]); ") . ($edges["u"] > 0 ? "" : "translate([0,0,$height - 0.01]) cube([$width - 0.01, $thick, 0.01]); "). ($edges["l"] > 0 ? "" : "cube([0.01, $thick, $height - 0.01]); ") . ($edges["r"] > 0 ? "" : "translate([$width - 0.01,0,0]) cube([0.01, $thick, $height - 0.01]); ") . "  } }  }";
}

function wall_bottom($box, $params, $name = "", $padding = "") {
    return wall($box, "bottom", $params, $name, $padding);
}

function wall_top($box, $params, $name = "", $padding = "") {
    return wall($box, "top", $params, $name, $padding);
}

function wall_left($box, $params, $name = "", $padding = "") {
    return wall($box, "left", $params, $name, $padding);
}

function wall_right($box, $params, $name = "", $padding = "") {
    return wall($box, "right", $params, $name, $padding);
}

function wall_back($box, $params, $name = "", $padding = "") {
    return wall($box, "back", $params, $name, $padding);
}

function wall_front($box, $params, $name = "", $padding = "") {
    return wall($box, "front", $params, $name, $padding);
}

function wall($box, $side, $params, $name = "", $padding = "") {

    if (strlen($padding)) {
        $box = padding($box, $padding);
    }

    $edges = [
        "l" => -1,
        "r" => -1,
        "d" => -1,
        "u" => -1,
    ];
    $rounds = [
        "ld" => 0,
        "lu" => 0,
        "rd" => 0,
        "ru" => 0,
    ];
    $width  = 0;
    $height = 0;
    $thick = $box["thick"];
    $color = $box["color"];
    $edgeThick = $box["edge-thick"];
    $close = true;
    $door = false;
    $opacity = 1;
    $render = true;
    $addPart = true;

    $edgeTransitions = [];
    $roundTransitions = [];

    switch ($side) {
        case "right":
        case "left":
            $width = $box["size"]["d"] - precision() * 2;
            $height = $box["size"]["v"] - precision() * 2;
            $edgeTransitions["bottom"] = "d";
            $edgeTransitions["top"] = "u";
            $edgeTransitions["back"] = "l";
            $edgeTransitions["front"] = "r";

            $roundTransitions["bottom-front"] = "rd";
            $roundTransitions["front-bottom"] = "rd";
            $roundTransitions["bottom-back"] = "ld";
            $roundTransitions["back-bottom"] = "ld";
            $roundTransitions["top-front"] = "ru";
            $roundTransitions["front-top"] = "ru";
            $roundTransitions["top-back"] = "lu";
            $roundTransitions["back-top"] = "lu";
            break;
        case "top":
        case "bottom":
            $width = $box["size"]["d"] - precision() * 2;
            $height = $box["size"]["h"] - precision() * 2;
            $edgeTransitions["left"] = "d";
            $edgeTransitions["right"] = "u";
            $edgeTransitions["back"] = "l";
            $edgeTransitions["front"] = "r";

            $roundTransitions["left-back"] = "ld";
            $roundTransitions["back-left"] = "ld";
            $roundTransitions["left-front"] = "rd";
            $roundTransitions["front-left"] = "rd";
            $roundTransitions["right-front"] = "ru";
            $roundTransitions["front-right"] = "ru";
            $roundTransitions["right-back"] = "lu";
            $roundTransitions["back-right"] = "lu";
            break;
        case "back":
        case "front":
            $width = $box["size"]["h"] - precision() * 2;
            $height = $box["size"]["v"] - precision() * 2;
            $edgeTransitions["bottom"] = "d";
            $edgeTransitions["top"] = "u";
            $edgeTransitions["left"] = "l";
            $edgeTransitions["right"] = "r";

            $roundTransitions["right-bottom"] = "rd";
            $roundTransitions["bottom-right"] = "rd";
            $roundTransitions["top-right"] = "ru";
            $roundTransitions["right-top"] = "ru";
            $roundTransitions["left-bottom"] = "ld";
            $roundTransitions["bottom-left"] = "ld";
            $roundTransitions["left-top"] = "lu";
            $roundTransitions["top-left"] = "lu";
            break;
    }

    foreach(explode(",", $params) as $pad) {
        $parts = explode("=", $pad);
        $key = trim($parts[0]);
        if (count($parts) > 1) {
            $valstr = trim($parts[1]);
            $value = doubleval($valstr);
        }
        switch ($key)
        {
            case "width":
                $width = _parse_value($width, $valstr);
                break;
            case "height":
                $height = _parse_value($height, $valstr);
                break;
            case "thick":
                $thick = $value;
                break;
            case "color":
                $color = $value;
                break;
            case "edge-thick":
                $edgeThick = $value;
                break;
            case "edge":
                $edges["l"] = ($valstr == "f" ? $edgeThick : $value);
                $edges["r"] = ($valstr == "f" ? $edgeThick : $value);
                $edges["u"] = ($valstr == "f" ? $edgeThick : $value);
                $edges["d"] = ($valstr == "f" ? $edgeThick : $value);
                break;
            case "round":
                $rounds["ld"] = _parse_value($height, $valstr);
                $rounds["lu"] = _parse_value($height, $valstr);
                $rounds["rd"] = _parse_value($height, $valstr);
                $rounds["ru"] = _parse_value($height, $valstr);
                break;
            case "close":
                $close = ($value != 0);
                break;
            case "door":
                $door = ($value != 0);
                break;
            case "render":
                $render = ($value != 0);
                break;
            case "addpart":
                $addPart = ($value != 0);
                break;
            case "opacity":
                $opacity = $value;
                break;
        }

        $tparts = explode("-", $key);
        $firstKey = array_shift($tparts);
        $lastPart = implode("-", $tparts);
        if ($firstKey == "round") {
            if (isset($roundTransitions[$lastPart])) {
                //echo "Round: $lastPart\r\n";
                $rounds[$roundTransitions[$lastPart]] = $value;
            }
        }
        else if ($firstKey == "edge" && $lastPart != null && strlen($lastPart) > 0) {
            if (isset($edgeTransitions[$lastPart])) {
                //echo "SET EDGE " . $edgeTransitions[$lastPart] . "\r\n";
                $edges[$edgeTransitions[$lastPart]] = ($valstr == "f" ? $edgeThick : $value);
            }
        }
    }

    if ($width > 0 && $height > 0) {
        //$opacity = ($door ? $doorOpacity : 1);
        switch ($side) {
            case "left":
                $name = str_replace("%W", "Левая стенка", $name);
                $boxPosition = ($box["position"]["h"]) . ", " . -($box["position"]["d"] + precision()) . ", " . ($box["position"]["v"] + precision());
                $edges["d"] = ($edges["d"] == -1 ? ($box["closed"]["d"] ? 0 : $edgeThick) : $edges["d"]);
                $edges["u"] = ($edges["u"] == -1 ? ($box["closed"]["u"] ? 0 : $edgeThick) : $edges["u"]);
                $edges["l"] = ($edges["l"] == -1 ? ($box["closed"]["b"] ? 0 : $edgeThick) : $edges["l"]);
                $edges["r"] = ($edges["r"] == -1 ? ($box["closed"]["f"] ? 0 : $edgeThick) : $edges["r"]);
                $scad = "translate([$boxPosition]) { translate([0, 0, 0]) { rotate([0, 0, -90]) {" . _scad_create($width, $height, $thick, $rounds, $edges, $color, $opacity) . " } } }";
                if ($render) {
                    _add_scad($scad, $name, $door);
                }
                $box = padding($box, "left=$thick");
                $box["closed"]["l"] = $close;
                break;
            case "right":
                $name = str_replace("%W", "Правая стенка", $name);
                $boxPosition = ($box["position"]["h"] + $box["size"]["h"] - $thick) . ", " . -($box["position"]["d"] + precision()) . ", " . ($box["position"]["v"] + precision());
                $edges["d"] = ($edges["d"] == -1 ? ($box["closed"]["d"] ? 0 : $edgeThick) : $edges["d"]);
                $edges["u"] = ($edges["u"] == -1 ? ($box["closed"]["u"] ? 0 : $edgeThick) : $edges["u"]);
                $edges["l"] = ($edges["l"] == -1 ? ($box["closed"]["b"] ? 0 : $edgeThick) : $edges["l"]);
                $edges["r"] = ($edges["r"] == -1 ? ($box["closed"]["f"] ? 0 : $edgeThick) : $edges["r"]);
                $scad = "translate([$boxPosition]) { translate([0, 0, 0]) { rotate([0, 0, -90]) {" . _scad_create($width, $height, $thick, $rounds, $edges, $color, $opacity) . " } } }";
                if ($render) {
                    _add_scad($scad, $name, $door);
                }
                $box = padding($box, "right=$thick");
                $box["closed"]["r"] = $close;
                break;
            case "top":
                $name = str_replace("%W", "Верх", $name);
                $boxPosition = ($box["position"]["h"] + precision()) . ", " . -($box["position"]["d"] + precision()) . ", " . ($box["position"]["v"] + $box["size"]["v"] - $thick);
                $edges["d"] = ($edges["d"] == -1 ? ($box["closed"]["l"] ? 0 : $edgeThick) : $edges["d"]);
                $edges["u"] = ($edges["u"] == -1 ? ($box["closed"]["r"] ? 0 : $edgeThick) : $edges["u"]);
                $edges["l"] = ($edges["l"] == -1 ? ($box["closed"]["b"] ? 0 : $edgeThick) : $edges["l"]);
                $edges["r"] = ($edges["r"] == -1 ? ($box["closed"]["f"] ? 0 : $edgeThick) : $edges["r"]);
                $scad = "translate([$boxPosition]) { translate([0, 0, $thick]) { rotate([0, 90, 0]) { rotate([0, 0, -90]) {" . _scad_create($width, $height, $thick, $rounds, $edges, $color, $opacity) . " } } } }";
                if ($render) {
                    _add_scad($scad, $name, $door);
                }
                $box = padding($box, "top=$thick");
                $box["closed"]["u"] = $close;
                break;
            case "bottom":
                $name = str_replace("%W", "Дно", $name);
                $boxPosition = ($box["position"]["h"] + precision()) . ", " . -($box["position"]["d"] + precision()) . ", " . ($box["position"]["v"]);
                $edges["d"] = ($edges["d"] == -1 ? ($box["closed"]["l"] ? 0 : $edgeThick) : $edges["d"]);
                $edges["u"] = ($edges["u"] == -1 ? ($box["closed"]["r"] ? 0 : $edgeThick) : $edges["u"]);
                $edges["l"] = ($edges["l"] == -1 ? ($box["closed"]["b"] ? 0 : $edgeThick) : $edges["l"]);
                $edges["r"] = ($edges["r"] == -1 ? ($box["closed"]["f"] ? 0 : $edgeThick) : $edges["r"]);
                $scad = "translate([$boxPosition]) { translate([0, 0, $thick]) { rotate([0, 90, 0]) { rotate([0, 0, -90]) {" . _scad_create($width, $height, $thick, $rounds, $edges, $color, $opacity) . " } } } }";
                if ($render) {
                    _add_scad($scad, $name, $door);
                }
                $box = padding($box, "bottom=$thick");
                $box["closed"]["d"] = $close;
                break;
            case "back":
                $name = str_replace("%W", "Задняя стенка", $name);
                $boxPosition = ($box["position"]["h"] + precision()) . ", " . -($box["position"]["d"]) . ", " . ($box["position"]["v"] + precision());
                $edges["d"] = ($edges["d"] == -1 ? ($box["closed"]["d"] ? 0 : $edgeThick) : $edges["d"]);
                $edges["u"] = ($edges["u"] == -1 ? ($box["closed"]["u"] ? 0 : $edgeThick) : $edges["u"]);
                $edges["l"] = ($edges["l"] == -1 ? ($box["closed"]["l"] ? 0 : $edgeThick) : $edges["l"]);
                $edges["r"] = ($edges["r"] == -1 ? ($box["closed"]["r"] ? 0 : $edgeThick) : $edges["r"]);
                $scad = "translate([$boxPosition]) { translate([0, -$thick, 0]) { rotate([0, 0, 0]) { rotate([0, 0, 0]) {" . _scad_create($width, $height, $thick, $rounds, $edges, $color, $opacity) . " } } } }";
                if ($render) {
                    _add_scad($scad, $name, $door);
                }
                $box = padding($box, "back=$thick");
                $box["closed"]["b"] = $close;
                break;
            case "front":
                $name = str_replace("%W", ($door ? "Фасад" : "Передняя стенка"), $name);
                $boxPosition = ($box["position"]["h"] + precision()) . ", " . -($box["position"]["d"] + $box["size"]["d"] - $thick) . ", " . ($box["position"]["v"] + precision());
                $edges["d"] = ($edges["d"] == -1 ? ($box["closed"]["d"] ? 0 : $edgeThick) : $edges["d"]);
                $edges["u"] = ($edges["u"] == -1 ? ($box["closed"]["u"] ? 0 : $edgeThick) : $edges["u"]);
                $edges["l"] = ($edges["l"] == -1 ? ($box["closed"]["l"] ? 0 : $edgeThick) : $edges["l"]);
                $edges["r"] = ($edges["r"] == -1 ? ($box["closed"]["r"] ? 0 : $edgeThick) : $edges["r"]);
                $scad = "translate([$boxPosition]) { translate([0, -$thick, 0]) { rotate([0, 0, 0]) { rotate([0, 0, 0]) {" . _scad_create($width, $height, $thick, $rounds, $edges, $color, $opacity) . " } } } }";
                if ($render) {
                    _add_scad($scad, $name, $door);
                }
                $box = padding($box, "front=$thick");
                $box["closed"]["f"] = $close;
                break;
        }
        if ($addPart) {
            _add_part($width, $height, $thick, $rounds, $edges, $name);
        }
    }

    if (strlen($padding)) {
        $box = padding($box, $padding, -1);
    }

    return $box;
}

function is_part(&$box) {
    return ($box["type"] == "part");
}

function is_between(&$box): bool {
    return ($box["type"] == "between");
}

function render_scad() {
    global $_scad, $_scad2, $_scadCuts;
    $pi = pathinfo($_SERVER["SCRIPT_NAME"]);

    $code = implode("\r\n", array_merge($_scad, $_scad2));

    if (count($_scadCuts) > 0) {
        $code = "difference() {\r\n union() {\r\n " . $code . "\r\n} \r\n" . implode("\r\n", $_scadCuts) . "\r\n}";
    }

    file_put_contents($pi["dirname"] . "/" . $pi["filename"] . ".scad", $code);
}

function render_json($rotateDirection = true) {
    global $_parts;
    $pi = pathinfo($_SERVER["SCRIPT_NAME"]);

    $script = file_get_contents($_SERVER["SCRIPT_NAME"]);
    if (mb_strpos($script, "//MEASURE") !== FALSE || mb_strpos($script, "//ИЗМЕРИТЬ") !== FALSE) {
        file_put_contents($pi["dirname"] . "/" . $pi["filename"] . ".json", "There are not measured parameters in source!");
        echo "There are not measured parameters in source!" . PHP_EOL;
        return;
    }

    $lines = [];
    foreach ($_parts as $part) {
        $width = floor($part["height"] - $part["edges"]["u"] - $part["edges"]["d"]);
        $height = floor($part["width"] - $part["edges"]["l"] - $part["edges"]["r"]);

        $edges = [
            $part["edges"]["l"],
            $part["edges"]["u"],
            $part["edges"]["r"],
            $part["edges"]["d"],
        ];

        $rounds = [
            $part["rounds"]["ld"],
            $part["rounds"]["lu"],
            $part["rounds"]["ru"],
            $part["rounds"]["rd"],
        ];

        if ($rotateDirection) {
            $edges = [
                $part["edges"]["d"],
                $part["edges"]["l"],
                $part["edges"]["u"],
                $part["edges"]["r"],
            ];
            $rounds = [
                $part["rounds"]["rd"],
                $part["rounds"]["ld"],
                $part["rounds"]["lu"],
                $part["rounds"]["ru"],
            ];
        }

        $lines[] = "ECHO: \"JSON:".json_encode([
            "name" => $part["name"],
            "width" => $rotateDirection ? $height : $width,
            "length" => $rotateDirection ? $width : $height,
            "thick" => $part["thick"],
            "edges" => $edges,
            "rounds" => $rounds,
        ]) . "\"";
    }
    file_put_contents($pi["dirname"] . "/" . $pi["filename"] . ".details.json.txt", implode("\r\n", $lines));
}


function render_parts_list($rotateDirection = true) {
    global $_parts;
    $pi = pathinfo($_SERVER["SCRIPT_NAME"]);

    $script = file_get_contents($_SERVER["SCRIPT_NAME"]);
    if (mb_strpos($script, "//MEASURE") !== FALSE || mb_strpos($script, "//ИЗМЕРИТЬ") !== FALSE) {
        file_put_contents($pi["dirname"] . "/" . $pi["filename"] . ".json", "There are not measured parameters in source!");
        echo "There are not measured parameters in source!" . PHP_EOL;
        return;
    }

    $lines = [ "Ширина;Высота;Толщина;Кромка (Н);Кромка (Л);Кромка (В);Кромка (П);Скругление (ПН);Скругление (ЛН);Скругление (ЛВ);Скругление (ПВ);Коментарий;Количество" ];
    $keys = [];
    foreach ($_parts as $part) {
        $width = floor($part["height"] - $part["edges"]["u"] - $part["edges"]["d"]);
        $height = floor($part["width"] - $part["edges"]["l"] - $part["edges"]["r"]);

        $edges = [
            $part["edges"]["l"],
            $part["edges"]["u"],
            $part["edges"]["r"],
            $part["edges"]["d"],
        ];

        $rounds = [
            $part["rounds"]["ld"],
            $part["rounds"]["lu"],
            $part["rounds"]["ru"],
            $part["rounds"]["rd"],
        ];

        if ($rotateDirection) {
            $edges = [
                $part["edges"]["d"],
                $part["edges"]["l"],
                $part["edges"]["u"],
                $part["edges"]["r"],
            ];
            $rounds = [
                $part["rounds"]["rd"],
                $part["rounds"]["ld"],
                $part["rounds"]["lu"],
                $part["rounds"]["ru"],
            ];
        }

        $line = implode(";", [ $rotateDirection ? $height : $width , $rotateDirection ? $width : $height , $part["thick"], $edges[0], $edges[1], $edges[2], $edges[3], $rounds[0], $rounds[1], $rounds[2], $rounds[3], $part["name"] ]);
        if (!isset($keys[$line])) {
            $keys[$line] = 1;
        } else {
            $keys[$line]++;
        }
        //$lines[] = ;
    }
    foreach ($keys as $line => $count) {
        $lines[] = "$line;$count";
    }
    file_put_contents($pi["dirname"] . "/" . $pi["filename"] . ".parts.csv", iconv("utf-8", "windows-1251", implode("\r\n", $lines)));
}

function view_cut($direction, $offset) {
    global $_scadCuts;
    $zone = 10000;
    switch ($direction) {
        case "up":
            $_scadCuts[] = "translate([-$zone, -$zone, $offset]) cube([$zone * 2, $zone * 2, $zone  * 2]);";
            break;
        case "down":
            $_scadCuts[] = "translate([-$zone, -$zone, -0.01]) cube([$zone * 2, $zone * 2, $offset]);";
            break;
        case "left":
            $_scadCuts[] = "translate([-0.01, -$zone, -0.01]) cube([$offset, $zone * 2, $zone  * 2]);";
            break;
        case "right":
            $_scadCuts[] = "translate([$offset, -$zone, -0.01]) cube([$zone * 2, $zone * 2, $zone  * 2]);";
            break;
        case "back":
            $_scadCuts[] = "translate([-$zone, -$offset, -0.01]) cube([$zone * 2, $zone * 2, $zone  * 2]);";
            break;
        case "front":
            $_scadCuts[] = "translate([-$zone, -$zone, -0.01]) cube([$zone * 2, $zone - $offset, $zone  * 2]);";
            break;
    }
}

function view_cut_up($offset) {
    view_cut("up", $offset);
}

function view_cut_down($offset) {
    view_cut("down", $offset);
}

function view_cut_left($offset) {
    view_cut("left", $offset);
}

function view_cut_right($offset) {
    view_cut("right", $offset);
}

function view_cut_back($offset) {
    view_cut("back", $offset);
}

function view_cut_front($offset) {
    view_cut("front", $offset);
}










/* TEST
$thick = thick();

$box = box(1000, 800, 600);
$box = wall($box, "bottom", "round-front-left=100,round-back-right=100", "Box: Bottom");
$box = wall($box, "top", "round-front-left=100,round-front-right=100,round-back-right=100", "Box: Top");
//$box = mebel_wall($box, "front", "close=0,door=1,edge=f", "Box: Front");
//$box = mebel_pading($box, "front=" . thick());
$box = wall($box, "left", "round-bottom-back=100,round-top-back=100,round-top-front=100", "Box: Left", "front=$thick");
//$box = mebel_pading($box, "front=" . thick(), -1);
$box = wall($box, "right", "", "Box: Right");
$box = wall($box, "back", "round-right-bottom=100,round-right-top=100,round-left-bottom=100,round-left-top=100", "Box: Back");

$boxes = split($box, "horizontal", "*,*", thick());
foreach ($boxes as $b) {
    if (is_between($b)) {
        $b = wall($b, "left", "", "Split", "front=$thick");
    }
}

$boxes2 = split(padding($boxes[0], "top=5,bottom=5"), "vertical", "*,*,*,*,*", 5);
foreach ($boxes2 as $b) {
    if (is_part($b)) {
        //echo "Box: " . floor($b["size"]["h"]) . "x" . floor($b["size"]["v"]) . "x" . floor($b["size"]["d"]) . PHP_EOL;

        $b = wall($b, "front", "edge=f,door=0,round-right-bottom=100,round-left-top=100", "MiniBox: Face", "rev,left=$thick,right=$thick");
        $b = padding($b, "left=4,right=4", -1);
        $b = padding($b, "left=10,right=10,back=10");
        
        $b = padding($b, "top=20");
        $b = wall($b, "front", "", "MiniBox: Front");
        $b = wall($b, "bottom", "", "MiniBox: Bottom");
        $b = wall($b, "left", "", "MiniBox: Left");
        $b = wall($b, "right", "", "MiniBox: Right");
        $b = wall($b, "back", "", "MiniBox: Back");
        
        
        
        //$b = padding($b, "top=20", -1);
    }
}

//view_cut("up", 410);

render_scad();
render_json();
//*/
<?php

function remove_zip_ext($string) {
  return str_replace('.zip', '', $string);
}

$uri = explode("/", $_SERVER['REQUEST_URI']);

if ($uri[1] != "api" || $uri[2] != "v1") {
  http_response_code(403);
  echo "403 Forbidden";
  exit;
}

$device     = strtolower($uri[3]);
$romType    = strtolower($uri[4]);
$base       = strtolower($uri[5]);
$actualDate = intval(strtolower(explode(".", $base)[2]));

if ($device == "" || $romType == "" || $base == "") {
  http_response_code(403);
  echo "403 Forbidden";
  exit;
}

$root = __DIR__;

if (!is_dir($root . '/builds/' . $device)) {
  http_response_code(404);
  echo "404 Not found";
  exit;
}

header("Connection: keep-alive");
header("Content-Type: application/json");

$output = array();
$builds = scandir($root . '/builds/' . $device);

foreach ($builds as $build) {
  if (!is_dir($build) && pathinfo($build, PATHINFO_EXTENSION) == "zip") {

    $file = explode('-', $build);
    
    $buildName    = remove_zip_ext(strtolower($file[0]));
    $buildVersion = remove_zip_ext(strtolower($file[1]));
    $buildDate    = remove_zip_ext(intval(strtolower($file[2])));
    $buildType    = remove_zip_ext(strtolower($file[3]));
    $buildDevice  = remove_zip_ext(strtolower($file[4]));

    $fullBuildPath = $root . '/builds/' . $device . '/' . $build;

    if (!file_exists($fullBuildPath . '.ignore') && $buildDevice == $device && $buildType == $romType && $actualDate <= $buildDate && file_exists($fullBuildPath . '.md5sum')) {
      $givenMd5 = explode('  ', file_get_contents($fullBuildPath . '.md5sum'))[0];
      $pass = false;

      if (file_exists($fullBuildPath . '.checked')) {
        $md5 = $givenMd5;
        $pass = true;
      } else {
        $md5 = md5_file($fullBuildPath);
        if ($md5 == $givenMd5) {
          fopen($fullBuildPath . '.checked', "w");
          $pass = true;
        } else {
          unlink($fullBuildPath);
        }
      }

      if ($pass === true) {
        $output[] = array(
          "datetime" => filemtime($fullBuildPath),
          "filename" => $build,
          "id"       => $md5,
          "romtype"  => $romType,
          "size"     => filesize($fullBuildPath),
          "url"      => "https://" . $_SERVER['HTTP_HOST'] . '/builds/' . $device . '/' . $build,
          "version"  => $buildVersion
        );
      }
    }
  }
}

echo json_encode(array("response" => $output));
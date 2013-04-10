<?php
$template = $scriptProperties['tpl'] ? $scriptProperties['tpl'] : $scriptProperties['thumbTpl'];
$crop = $scriptProperties['crop'] ? $scriptProperties['crop'] : 0;
$tvPrefix = $scriptProperties['tvPrefix'] ? $scriptProperties['tvPrefix'] : '';
$modx->setPlaceHolder('tvPrefix', $tvPrefix);
$processImage = $scriptProperties['processImage'] ? $scriptProperties['processImage'] : '';
$modx->setPlaceHolder('processImage', $processImage);
$total = empty($scriptProperties['limit']) ? 0 : $scriptProperties['limit'];
$scriptProperties['limit'] = $lineLimit;
$scriptProperties['includeTVs'] = $lineLimit;
$scriptProperties['tpl'] = '@INLINE "[[+id]]":"[[+'.$tvPrefix.$processImage.']]"';
$scriptProperties['thumbTpl'] = 'tpl.AlignImage';
$scriptProperties['outputSeparator'] = ',';
$scriptProperties['linkToImage'] = 1;
$output = '';

$Lines = array();
$i = 0;
$isItems = true;
while ($isItems) {
  $scriptProperties['offset'] = $scriptProperties['start'] = $i;
  $subTotal = $scriptProperties['offset'] + $lineLimit;
  if ($total && $subTotal > $total) $scriptProperties['limit'] = $total - $scriptProperties['offset'];
  $result = $modx->runSnippet($snippet, $scriptProperties);
  if (!$result) break;
  if (substr($result,-1,1) == ',') $result = substr($result,0,-1);
  $Lines[] = '{' . $result . '}';
  $total = $total ? $total : $modx->getPlaceholder('total');
  $i = $i + $lineLimit;
  if ($i >= $total) $isItems = false;
}

foreach ($Lines as $line) {
    $line = $modx->fromJSON($line);
    $images = $w = $h = array();
    foreach ($line as $id => $img) {
        if (substr($img,0,1) == '/') $img = substr($img,1);
        if ($crop) {
          $im = new Imagick($img);
          $im->trimImage(0);
          $path_info = pathinfo($img);
          $cropedFile  = $path_info['dirname'].'/croped-'.$lineWidth.'/';
          if (!file_exists($cropedFile)) mkdir($cropedFile);
          $cropedFile .= $path_info['basename'];
          $im->writeImage($cropedFile);
          $img = $cropedFile;
        }
        $size = getimagesize($img);
        $w[] = $size[0];
        $h[] = $size[1];
        $images[$id] = $img;
    }
    foreach ($w as $k => $w_old) {
        $w[$k] = floor($w_old * min($h) / $h[$k]);
    }
    $lineHeight = floor(min($h) * $lineWidth / array_sum($w));
    foreach ($images as $id => $image) {
        $ph = $tvPrefix.$processImage;
        $im = new Imagick($image);
        $im->resizeImage(0,$lineHeight,0,1);
        $path_info = pathinfo($image);
        $image  = $path_info['dirname'].'/h-'.$lineHeight.'/';
        if (!file_exists($image)) mkdir($image);
        $image .= $path_info['basename'];
        $im->writeImage($image);
        $output .= $modx->getChunk($template, array($ph => $image, 'id' => $id, 'album' => $scriptProperties['album']))."\n";
    }
}

return $output;

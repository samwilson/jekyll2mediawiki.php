<?php

require __DIR__ . '/vendor/autoload.php';

$inputDir = __DIR__;
$outputDir = __DIR__ . '/wiki';

if (!is_dir($outputDir)) {
    mkdir($outputDir);
}

foreach (scandir($inputDir . '/_posts') as $mdFile) {
    if ($mdFile[0] === '.') {
        continue;
    }
    echo $mdFile . "\n";
    $contents = file_get_contents($inputDir . '/_posts/' . $mdFile);
    preg_match('/^---$(.*)^---$/sm', $contents, $matches);
    try {
        $data = Spyc::YAMLLoadString($matches[1]);
    } catch (\Exception $e) {
        echo "Unable to parse Yaml:\n".$matches[1]."\n";
        throw $e;
    }

    // Template.
    $date = date('Y-m-d H:i:s', strtotime($data['date']));
    $tpl = "{{log entry |date=" . $data['date'] . "}}";

    // Wikitext
    $cmd = 'pandoc --from markdown --to mediawiki "' . $inputDir . '/_posts/' . $mdFile . '"';
    $wikitext = shell_exec($cmd);

    // Categories.
    $cats = '';
    $categories = isset($data['categories']) ? $data['categories'] : [];
    $tags = isset($data['tags']) ? $data['tags'] : [];
    foreach (array_merge($categories, $tags) as $cat) {
        $cats .= "[[Category:" . ucfirst($cat) . "]]\n";
    }

    // Output it all.
    $title = isset($data['title']) ? preg_replace('/[ \/]/', '_', $data['title']) : 'Post ' . $data['id'];
    $outfile = $outputDir . '/' . $title . '.txt';
    $origMeta = "<!-- Original WordPress metadata:\n".$matches[1]."\n-->\n";
    file_put_contents($outfile, $tpl . "\n\n" . trim($wikitext) ."\n\n$cats\n\n$origMeta");

    //exit();
}

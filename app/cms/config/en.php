<?php

$setting_detail['web'] = array(
    'name' => 'Website setting', 
    'list' => [
        'title' => array(
            'name' => 'Website Name', 
            'describe' => 'Name of the website', 
            'type' => array('text', 'name', '40')
        ), 
    ]
);

$setting_detail['content'] = array(
    'name' => 'Content Setting', 
    'list' => [
        'upload' => array(
            'name' => 'Upload Path', 
            'describe' => 'The directory save teh upload files', 
            'type' => array('text', '', '40')
        ), 
        'get_remote_img' => array(
            'name' => 'Get Image', 
            'describe' => 'Download content images from other website', 
            'type' => array('radio', array('Enable'=>'true', 'Disable'=>'false'))
        ), 
    ]
);

$setting_detail['template'] = array(
    'name' => 'Template Setting', 
    'list' => [
        'name' => array(
            'name' => 'Name', 
            'describe' => 'Main template of a set', 
            'type' => array('text', '', '10')
        ), 
        'path' => array(
            'name' => 'Path', 
            'describe' => 'The path which save the template files', 
            'type' => array('text', '', '30')
        ), 
        'style' => array(
            'name' => 'Style', 
            'describe' => 'Style for template', 
            'type' => array('text', '_', '20')
        ), 
    ]
);

$setting_detail['list'] = array(
    'name' => 'Parameter for Artile List', 
    'list' => [
        'txt' => array(
            'name' => 'Text List', 
            'describe' => 'How many artile will be show in subject only list.', 
            'type' => array('text', 'digital', '3')
        ), 
        'img' => array(
            'name' => 'Image List', 
            'describe' => 'How many artile will be show in image with subject list.', 
            'type' => array('text', 'digital', '3')
        ), 
        'mix' => array(
            'name' => 'Mix List', 
            'describe' => 'How many artile will be show in image with description list.', 
            'type' => array('text', 'digital', '3')
        ), 
        'rss' => array(
            'name' => 'Rss List', 
            'describe' => 'How many artile will be show in RSS.', 
            'type' => array('text', 'digital', '3')
        ), 
    ]
);

$setting_detail['expire'] = array(
    'name' => 'Expiration setting for html cache', 
    'list' => [
        'default' => array(
            'name' => 'Default', 
            'describe' => 'The expire time for non-specified pages', 
            'type' => array('text', 'digital', '6')
        ), 
        'index' => array(
            'name' => 'Index', 
            'describe' => 'The expire time for index page', 
            'type' => array('text', 'digital', '6')
        ), 
        'list' => array(
            'name' => 'List', 
            'describe' => 'The expire time for list pages', 
            'type' => array('text', 'digital', '6')
        ), 
        'tag' => array(
            'name' => 'Tag', 
            'describe' => 'The expire time for tag page', 
            'type' => array('text', 'digital', '6')
        ), 
        'read' => array(
            'name' => 'Content', 
            'describe' => 'The expire time for content page', 
            'type' => array('text', 'digital', '6')
        ), 
    ]
);

$setting_detail['watermark'] = array(
    'name' => 'Watermark Setting', 
    'list' => [
        'mode' => array(
            'name' => 'Mode', 
            'describe' => 'Add Watermark to artile content or images', 
            'type' => array('checkbox', array('checkbox', array('Text'=>1, 'Image'=>2)))
        ), 
        'txt' => array(
            'name' => 'Jam String', 
            'describe' => 'Jam String will be added to article content', 
            'type' => array('text', '', '30')
        ), 
        'img' => array(
            'name' => 'Watermark', 
            'describe' => 'Image or any text that will be added to images', 
            'type' => array('text', '', '30')
        ), 
        'position' => array(
            'name' => 'Position', 
            'describe' => 'Where to put the watermark', 
            'type' => array('select', array('select', array('Right-Bottom'=>1, 'Right-Top'=>2, 'Left-Bottom'=>3, 'Left-Top'=>4, 'Left-Middle'=>5, 'Right-Middle'=>6, 'Middle-Top'=>7, 'Middle-Bottom'=>8, 'Center'=>9)))
        ), 
        'img_rate' => array(
            'name' => 'Rate', 
            'describe' => 'Control the size of Watermark image', 
            'type' => array('text', 'digital', '2')
        ), 
        'txt_font' => array(
            'name' => 'Font File', 
            'describe' => 'The font file for the text watermark', 
            'type' => array('text', '', '40')
        ), 
        'txt_fontsize' => array(
            'name' => 'Font Size', 
            'describe' => 'The size of text watermark, in pixal', 
            'type' => array('text', 'digital', '2')
        ), 
        'txt_fontcolor' => array(
            'name' => 'Font Color', 
            'describe' => 'The color of text watermark (HTML color code like #000000)', 
            'type' => array('text', '', '7')
        ), 
        'txt_bgcolor' => array(
            'name' => 'Background Color', 
            'describe' => 'The background color of text watermark (HTML color code like #000000)', 
            'type' => array('text', '', '7')
        ), 
        'alpha' => array(
            'name' => 'Transparent Level', 
            'describe' => 'The transparent level of watermark (0-100)', 
            'type' => array('text', 'digital', '3')
        ), 
        'credit' => array(
            'name' => 'Credit', 
            'describe' => 'Credit information that will be added to the end of every line', 
            'type' => array('text', 'name', '30')
        ), 
    ]
);
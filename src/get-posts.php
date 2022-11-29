<?php
require_once "functions.php";
global $chatId, $botToken, $url, $neededSpecs;

$lastToken = trim(file_get_contents("last_token"));

// Main

$response = divarRequest($url);

$postsList = $response->web_widgets->post_list;

for ($i = 0; $i < count($postsList); $i++) {
    $post = $postsList[$i]->data;

    if ($post->token === $lastToken)
        break;

    processPost($post);
}

if ($lastToken !== $postsList[0]->data->token)
    file_put_contents("last_token", $postsList[0]->data->token);
else
    echo "There is no any new post...";

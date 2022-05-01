<?php
require_once "config.php";

function processPost(object $post) {
    global $chatId;

    $token = $post->token;

    $details = divarRequest("https://api.divar.ir/v5/posts/" . $token)->widgets;
    $details->token = $token;

    $caption = getCaption($details);
    $message = getMessage($details, $caption);
    $commonMessageData = [
        "chat_id" => $chatId,
        "parse_mode" => "HTML"
    ];

    $send = bot(
        $message["method"],
        $message["data"] + $commonMessageData,
        true
    );

    if (!$send->ok)
        bot("sendMessage", ["text" => "check <a href='https://divar.ir/v/$token'>this</a> out!"] + $commonMessageData);
}

function getMessage($post, $caption) {
    $imagesCount = count($post->web_images);

    if ($imagesCount === 0)
        $message = [
            "method" => "sendMessage",
            "data" => [
                "text" => $caption,
                "parse_mode" => "HTML"
            ]
        ];
    else if ($imagesCount === 1)
        $message = [
            "method" => "sendPhoto",
            "data" => [
                "photo" => $post->web_images[0][1]->src,
                "caption" => $caption,
                "parse_mode" => "HTML"
            ]
        ];
    else {
        $media = [];

        for ($i = 0; $i < $imagesCount; $i++) {
            $image = $post->web_images[$i];

            $media[] = [
                "type" => "photo",
                "media" => $image[1]->src
            ];
        }

        $media[0]["caption"] = $caption;
        $media[0]["parse_mode"] = "HTML";

        $message = [
            "method" => "sendMediaGroup",
            "data" => [
                "media" => json_encode($media)
            ]
        ];
    }

    return $message;
}

function getCaption($post) {
    global $neededSpecs;

    $price = null;
    $specifics = "";
    foreach ($post->list_data as $spec) {
        if (!in_array($spec->title, $neededSpecs))
            continue;

        if ($spec->title === "قیمت") {
            $price = $spec->value;
            continue;
        }

        $specifics .= "$spec->title: <b>$spec->value</b>\n";
    }

    return "آگهی " . $post->header->title . " (<b>" . $price . "</b>)"
        . PHP_EOL . PHP_EOL . htmlspecialchars($post->description)
        . PHP_EOL . PHP_EOL . $specifics
        . PHP_EOL . "محل: " . "<b>" . $post->header->place . "</b>"
        . PHP_EOL . "تلفن: " . "<b>" . $post->contact->phone . "</b>"
        . PHP_EOL . PHP_EOL . "<a href='https://divar.ir/v/$post->token'>🔗 مشاهده آگهی</a>";
}

function divarRequest($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/101.0.4951.41 Safari/537.36 Vivaldi/4.3");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Origin: https://divar.ir",
        "Referer: https://divar.ir/",
        "Accept: application/json, text/plain, */*"
    ]);

    $response = json_decode(curl_exec($ch));

    if (curl_errno($ch) || !$response)
        die("Error:" . curl_error($ch));

    curl_close($ch);

    return $response;
}

function bot(string $method, array $data = null, bool $return = false) {
    global $botToken;

    $url = "https://api.telegram.org/bot$botToken/$method";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if (!empty($data))
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

    $result = curl_exec($ch);

    if (curl_error($ch))
        return false;
    else
        return $return ? json_decode($result) : null;
}

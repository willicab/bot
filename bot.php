<?php
define('TOKEN', "BOT_TOKEN");

$doc = file_get_contents('api-doc-8.x.json');
$json_doc = json_decode($doc, true);
$base_url = "https://laravel.com/api/8.x/";
$update_id = 0;

# Loop infinito hasta que se mate el programa
while(1) {
    $str = sendMethod("getUpdates", ["offset"=>($update_id + 1)]);
    $json = json_decode($str);
    foreach ($json->result as $result) {
        $update_id = $result->update_id;
        # Inicia proceso para los inline query
        if (property_exists($result, 'inline_query')) {
            $query = $result->inline_query->query;
            $inline_query_id = $result->inline_query->id;
            # Se buscará a partir de 3 caracteres en el término de búsqueda
            if (strlen($query) > 3) {
                $result_doc = [];
                $count = 0;
                foreach ($json_doc as $doc) {
                    if (strpos($doc['doc'], $query) !== false) {
                        $message_text = str_replace(".", "\\.", "[{$doc['name']}]($base_url{$doc['link']})\n{$doc['doc']}");
                        $result_doc[] =     [
                            "type" => "article",
                            "id" => ++$count,
                            "title" => $doc['name'],
                            "url" => $base_url.$doc['link'],
                            "hide_url" => true,
                            "description" => $doc['doc'],
                            "input_message_content" => [
                                "parse_mode" => "MarkdownV2",
                                "message_text" => $message_text
                            ],
                            "thumb_url" => "https://www.yoelprogramador.com/wp-content/uploads/2018/11/proxy.duckduckgo.com_.jpg"
                        ];
                        # 50 es el límite de resultados de la API
                        if (count($result_doc) >= 50) {
                            break;
                        }
                    }
                }
                sendMethod("answerInlineQuery", [
                    "inline_query_id" => "$inline_query_id",
                    "results" => json_encode($result_doc)
                ]);
            }
        }
    }
    sleep(1);
}

# Función para enviar los métodos a la API
function sendMethod($method, $params = array()){
    $url   = "https://api.telegram.org/bot".TOKEN."/$method";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:multipart/form-data"));
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    return curl_exec($ch);
}

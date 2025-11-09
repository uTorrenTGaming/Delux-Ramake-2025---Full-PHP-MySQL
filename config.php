<?php
// config.php
// Este arquivo foi fornecido mas não está sendo usado
// pelos outros scripts (login, index, etc.)
// Ele parece ser para uma API do YouTube.

return (object) [
    // coloque sua API key aqui entre as aspas:
    'youtube_api_key' => 'AIzaSyBwDiotKkou6x90DhXOMd321PE_FiGhVFk',

    // Query padrão (pode usar 'shorts' ou qualquer termo)
    'default_query' => 'shorts',

    // Quantidade por chamada (1-50)
    'maxResults' => 8,

    // Cache em segundos (reduce hits to YouTube API)
    'cache_ttl' => 60 * 5, // 5 minutos

    // Diretório de cache (relativo a este arquivo)
    'cache_dir' => __DIR__ . '/cache'
];
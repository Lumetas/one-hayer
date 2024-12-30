<?php
function includeFromString($content) {
    // Регулярное выражение для поиска строк вида (include "file.kbd"),
    // которые не предшествуют ;;
    $pattern = '/(?<!;;)\(include\s+"(.+?)"\)/m';
    
    // Функция обратного вызова для обработки найденных строк
    $callback = function($matches) {
        $includeFilePath = trim($matches[1]);

        // Проверяем, существует ли файл, который мы хотим включить
        if (file_exists($includeFilePath)) {
            // Если файл существует, считываем его содержимое
            return file_get_contents($includeFilePath);
        } else {
            return "File not found: " . $includeFilePath;
        }
    };
    
    // Заменяем найденные строки на содержимое файлов
    $modifiedContent = preg_replace_callback($pattern, $callback, $content);
    
    return $modifiedContent;
}

function containsIncludes(string $content): bool {
    // Регулярное выражение для поиска строк вида (include "file.kbd"),
    // которые не предшествуют ;;
    $pattern = '/(?<!;;)\(include\s+"(.+?)"\)/m';
    
    // preg_match проверяет, есть ли хотя бы одно соответствие
    return preg_match($pattern, $content) === 1;
}




function parseKeysFromContent($content) {
    // Ищем содержимое между (defsrc и )
    preg_match('/\(defsrc\s*(.*?)\s*\)/s', $content, $matches);
    
    if (isset($matches[1])) {
        // Заменяем последовательности пробелов и табуляции на запятую
        $lines = preg_split('/[\s,]+/', $matches[1]);
        
        // Удаляем пустые значения
        $result = array_filter(array_map('trim', $lines), function($line) {
            return !empty($line);
        });

        // Возвращаем массив значений
        return array_values($result);
    }
    
    return []; // Возврат пустого массива, если не найдено
}

function disable_keys($content, $callback) {
    // Используем preg_replace_callback с учетом ";;" перед фрагментом
    return preg_replace_callback('/;;\s*\(disable_keys\s+([^);]*?)\)/', function($matches) {
        // Если фрагмент находится после ";;", просто возвращаем его как есть
        return $matches[0];
    }, preg_replace_callback('/\(disable_keys\s+([^);]*?)\)/', function($matches) use ($callback) {
        // Извлекаем символы из найденной строки
        $keys = preg_split('/[\s,]+/', trim($matches[1]));

        // Отфильтровываем пустые значения
        $keys = array_filter($keys, function($key) {
            return !empty(trim($key));
        });

        // Вызываем callback функцию и получаем результат
        $replacement = $callback(array_values($keys));

        // Если callback возвращает пустую строку, просто возвращаем пустую строку
        if (trim($replacement) === '') {
            return ''; // Удаляем фрагмент
        }
        
        // Возвращаем результат без внешних скобок
        return $replacement; // Удаляем внешние скобки
    }, $content));
}

function disable_all_keys($content, $callback) {
    // Используем preg_replace_callback с учетом ";;" перед фрагментом
    return preg_replace_callback('/;;\s*\(disable_all_keys\s+([^);]*?)\)/', function($matches) {
        // Если фрагмент находится после ";;", просто возвращаем его как есть
        return $matches[0];
    }, preg_replace_callback('/\(disable_all_keys\s+([^);]*?)\)/', function($matches) use ($callback) {
        // Извлекаем символы из найденной строки
        $keys = preg_split('/[\s,]+/', trim($matches[1]));

        // Отфильтровываем пустые значения
        $keys = array_filter($keys, function($key) {
            return !empty(trim($key));
        });

        // Вызываем callback функцию и получаем результат
        $replacement = $callback(array_values($keys));

        // Если callback возвращает пустую строку, просто возвращаем пустую строку
        if (trim($replacement) === '') {
            return ''; // Удаляем фрагмент
        }
        
        // Возвращаем результат без внешних скобок
        return $replacement; // Удаляем внешние скобки
    }, $content));
}

function disable_keys_callback($arr){
    $result = [];
    for ($i = 0; $i < count($arr); $i += 1){
        $result[] = $arr[$i]." @disable";
    }
    return implode("\n", $result);
}

function disable_all_keys_callback($arr){
    global $keys;
    if (count($arr) == 0){ 

        $result = [];
        for ($i = 0; $i < count($keys); $i += 1){
            $result[] = $keys[$i]." @disable";
        }

    } else {
        
        $result = [];
        for ($i = 0; $i < count($keys); $i += 1){
            if (in_array($keys[$i], $arr)){
                continue;
            } else {
                $result[] = $keys[$i]." @disable";
            }
            
        }
    }

    return implode("\n", $result);
}


// Пример использования
$filePath = $argv[1];
$content = file_get_contents($filePath);

while (containsIncludes($content)){
    $content = includeFromString($content);
}
$keys = parseKeysFromContent($content);
$content = disable_keys($content, "disable_keys_callback");
$content = disable_all_keys($content, "disable_all_keys_callback");

if (isset($argv[2]) && isset($argv[3])){
    $content = str_replace(explode(" ", $argv[2]), explode(" ", $argv[3]), $content);

}


file_put_contents("kanata.kbd", $content);

<?php

declare(strict_types=1);

namespace IsraelNogueira\FastRouter;

/**
 * Route Parser - Handle regex patterns and parameter extraction
 * 
 * @package IsraelNogueira\FastRouter
 * @author Israel Nogueira <israel@feats.com>
 * @license GPL-3.0-or-later
 */
class RouteParser
{
    /**
     * Parse route pattern to regex
     */
    public static function parseToRegex(string $pattern): string
    {
        // Wildcard total
        if ($pattern === '*' || $pattern === '.*' || $pattern === '') {
            return '#^.*$#';
        }

        // Remove trailing slashes
        $pattern = trim($pattern, '/');

        if ($pattern === '*' || $pattern === '.*' || $pattern === '') {
            return '#^.*$#';
        }

        // Processa ANTES de escapar:
        // 1. Extrai blocos opcionais aninhados e parâmetros obrigatórios
        //    substituindo por placeholders numerados
        $placeholders = [];
        $index = 0;

        // Substitui parâmetros obrigatórios {param} ou {param:regex}
        $pattern = preg_replace_callback('/\{([^}]+)\}/', function($matches) use (&$placeholders, &$index) {
            $key = '__PARAM_' . $index++ . '__';
            $placeholders[$key] = '(' . self::getRegexForParam($matches[1]) . ')';
            return $key;
        }, $pattern);

        // Substitui [/*/] (wildcard opcional)
        $pattern = preg_replace_callback('/\[\/\*\/\]/', function($matches) use (&$placeholders, &$index) {
            $key = '__PARAM_' . $index++ . '__';
            $placeholders[$key] = '(?:/(.+))?';
            return $key;
        }, $pattern);

        // Substitui blocos opcionais [/.../.] de dentro pra fora (aninhados)
        // Repete até não ter mais colchetes opcionais
        $maxIterations = 10;
        while ($maxIterations-- > 0 && preg_match('/\[([^\[\]]+)\]/', $pattern)) {
            $pattern = preg_replace_callback('/\[([^\[\]]+)\]/', function($matches) use (&$placeholders, &$index) {
                $inner = $matches[1]; // conteúdo dentro dos []
                $key = '__OPT_' . $index++ . '__';
                $placeholders[$key] = '(?:' . $inner . ')?';
                return $key;
            }, $pattern);
        }

        // Substitui wildcard * solto
        $pattern = str_replace('*', '__WILDCARD__', $pattern);

        // Agora escapa os caracteres especiais do que sobrou
        $pattern = preg_replace_callback('/[.\[\](){}^$|*+?\\\\]/', function($matches) {
            return '\\' . $matches[0];
        }, $pattern);

        // Restaura wildcard solto
        $pattern = str_replace('__WILDCARD__', '.+', $pattern);

        // Restaura todos os placeholders
        foreach ($placeholders as $key => $value) {
            $pattern = str_replace($key, $value, $pattern);
        }

        return '#^' . $pattern . '$#';
    }

    /**
     * Extract regex pattern from parameter definition
     */
    private static function getRegexForParam(string $param): string
    {
        if (strpos($param, ':') !== false) {
            [, $regex] = explode(':', $param, 2);
            return $regex;
        }
        return '[^/]+';
    }

    /**
     * Extract parameter names from pattern
     * 
     * @return array<string>
     */
    public static function extractParamNames(string $pattern): array
    {
        $names = [];

        // Parâmetros nomeados {param} e {param:regex}
        if (preg_match_all('/\{([^}:]+)(?::[^}]+)?\}/', $pattern, $matches)) {
            $names = $matches[1];
        }

        // Wildcards opcionais [/*/] — cada um vira 'wildcard'
        $count = substr_count($pattern, '[/*/]');
        for ($i = 0; $i < $count; $i++) {
            $names[] = 'wildcard';
        }

        return $names;
    }

    /**
     * Match URL against pattern and extract parameters
     * 
     * @return array{status: bool, params: array<string, mixed>, regex: string}
     */
    public static function match(string $pattern, string $url): array
    {
        $regex = self::parseToRegex($pattern);
        $url   = trim($url, '/');

        $result = [
            'status' => false,
            'params' => [],
            'regex'  => $regex,
        ];

        if (preg_match($regex, $url, $matches)) {
            array_shift($matches);

            $paramNames = self::extractParamNames($pattern);

            foreach ($paramNames as $i => $name) {
                $result['params'][$name] = $matches[$i] ?? null;
            }

            $result['status'] = true;
        }

        return $result;
    }

    /**
     * Get current URL path from request
     */
    public static function getCurrentPath(): string
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';

        if (($pos = strpos($path, '?')) !== false) {
            $path = substr($path, 0, $pos);
        }

        return $path;
    }

    /**
     * Normalize path by removing duplicate slashes
     */
    public static function normalizePath(string $path): string
    {
        $path = preg_replace('#/+#', '/', $path);
        return '/' . trim($path, '/');
    }
}
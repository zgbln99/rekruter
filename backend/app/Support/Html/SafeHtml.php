<?php

namespace App\Support\Html;

/**
 * Minimalne czyszczenie HTML opisu ogłoszenia przed wyświetleniem na publicznej
 * stronie. Treść tworzy zaufana rekruterka (panel), więc to dodatkowy bezpiecznik:
 * usuwamy skrypty/style, atrybuty zdarzeń (on*) i adresy javascript:.
 */
class SafeHtml
{
    private const ALLOWED = '<p><br><ul><ol><li><strong><b><em><i><u><h2><h3><h4><a><blockquote>';

    public static function clean(?string $html): string
    {
        $html = (string) $html;
        if (trim($html) === '') {
            return '';
        }

        // Zwykły tekst (bez tagów blokowych) → akapity + łamania linii,
        // żeby opis nie był jedną ścianą tekstu.
        if (! preg_match('/<(p|ul|ol|li|br|h[1-6]|div|table)\b/i', $html)) {
            $blocks = preg_split('/\n{2,}/', trim($html)) ?: [];
            $out = '';
            foreach ($blocks as $block) {
                $block = trim($block);
                if ($block === '') {
                    continue;
                }
                $out .= '<p>'.nl2br(e($block)).'</p>';
            }

            return $out;
        }

        // Usuń całe bloki skryptów/styli wraz z zawartością.
        $html = preg_replace('#<(script|style|iframe)[^>]*>.*?</\1>#is', '', $html) ?? '';

        // Tylko dozwolone tagi.
        $html = strip_tags($html, self::ALLOWED);

        // Usuń atrybuty zdarzeń (onclick=...) i niebezpieczne href-y.
        $html = preg_replace('#\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html) ?? $html;
        $html = preg_replace('#href\s*=\s*("|\')\s*javascript:[^"\']*\1#i', 'href="#"', $html) ?? $html;

        return trim($html);
    }
}

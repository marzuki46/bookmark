<?php

declare(strict_types=1);

namespace App\Services;

class ChromeBookmarkParser
{
    /**
     * Parse a Chrome HTML bookmark file and return structured data.
     *
     * Chrome bookmark HTML format:
     * <!DOCTYPE NETSCAPE-Bookmark-file-1>
     * <DL><p>
     *   <DT><H3>Folder Name</H3>
     *   <DL><p>
     *     <DT><A HREF="url" ADD_DATE="timestamp">Title</A>
     *   </DL><p>
     * </DL><p>
     */
    public function parse(string $html): array
    {
        // Normalize line endings
        $html = str_replace(["\r\n", "\r"], "\n", $html);

        $bookmarks = [];
        $this->parseDL($html, 0, strlen($html), $bookmarks, '');

        return $bookmarks;
    }

    private function parseDL(string $html, int $start, int $end, array &$bookmarks, string $folder): void
    {
        // Find <DL> and </DL> within range
        $dlOpen = stripos($html, '<DL>', $start);
        $dlClose = stripos($html, '</DL>', $start);

        if ($dlOpen === false || $dlOpen >= $end) {
            return;
        }

        // Find the matching </DL> for this <DL>
        $depth = 0;
        $pos = $dlOpen;
        $matchingClose = null;

        while ($pos < $end) {
            $nextOpen = stripos($html, '<DL>', $pos + 4);
            $nextClose = stripos($html, '</DL>', $pos + 5);

            if ($nextClose === false || $nextClose >= $end) {
                break;
            }

            if ($nextOpen !== false && $nextOpen < $nextClose) {
                $depth++;
                $pos = $nextOpen + 4;
            } else {
                if ($depth === 0) {
                    $matchingClose = $nextClose;
                    break;
                }
                $depth--;
                $pos = $nextClose + 5;
            }
        }

        if ($matchingClose === null) {
            return;
        }

        // Content between <DL>...</DL>
        $contentStart = $dlOpen + 4;
        $contentEnd = $matchingClose;

        // Parse items within this DL
        $this->parseChildren($html, $contentStart, $contentEnd, $bookmarks, $folder);
    }

    private function parseChildren(string $html, int $start, int $end, array &$bookmarks, string $folder): void
    {
        $pos = $start;

        while ($pos < $end) {
            // Skip whitespace
            $pos = $this->skipWhitespace($html, $pos, $end);
            if ($pos >= $end) {
                break;
            }

            // Look for <DT>
            $dtPos = stripos($html, '<DT>', $pos);
            if ($dtPos === false || $dtPos >= $end) {
                break;
            }

            $pos = $dtPos + 4;

            // Check what follows: <A> (bookmark) or <H3> (folder)
            $pos = $this->skipWhitespace($html, $pos, $end);

            $nextTag = $this->getNextTag($html, $pos, $end);

            if ($nextTag === 'A') {
                // Parse bookmark
                $bookmark = $this->parseBookmark($html, $pos, $end);
                if ($bookmark !== null) {
                    $bookmark['folder'] = $folder;
                    $bookmarks[] = $bookmark;
                }
                // Move past this <A>...</A>
                $aClose = stripos($html, '</A>', $pos);
                if ($aClose !== false && $aClose < $end) {
                    $pos = $aClose + 4;
                } else {
                    break;
                }
            } elseif ($nextTag === 'H3') {
                // Parse folder
                $folderName = $this->parseH3($html, $pos, $end);
                if ($folderName !== null) {
                    $newFolder = $folder ? $folder.' > '.$folderName : $folderName;

                    // Find the DL inside this folder and recurse
                    $this->parseDL($html, $pos, $end, $bookmarks, $newFolder);
                }

                // Move past this folder's DL
                $folderDlClose = $this->findMatchingDLClose($html, $pos, $end);
                if ($folderDlClose !== false) {
                    $pos = $folderDlClose + 5;
                } else {
                    break;
                }
            } else {
                $pos++;
            }
        }
    }

    private function parseBookmark(string $html, int $start, int $end): ?array
    {
        // Find <A ...>
        $aOpen = stripos($html, '<A ', $start);
        if ($aOpen === false || $aOpen >= $end) {
            return null;
        }

        // Find end of opening tag
        $aTagEnd = stripos($html, '>', $aOpen);
        if ($aTagEnd === false || $aTagEnd >= $end) {
            return null;
        }

        $aTag = substr($html, $aOpen, $aTagEnd - $aOpen + 1);

        // Extract HREF
        $href = $this->extractAttribute($aTag, 'HREF');
        if (empty($href)) {
            return null;
        }

        // Extract ADD_DATE
        $addDate = $this->extractAttribute($aTag, 'ADD_DATE');

        // Find </A>
        $aClose = stripos($html, '</A>', $aTagEnd);
        if ($aClose === false || $aClose >= $end) {
            $aClose = $end;
        }

        // Extract title (between > and </A>)
        $title = trim(substr($html, $aTagEnd + 1, $aClose - $aTagEnd - 1));

        // Decode HTML entities in title
        $title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');

        return [
            'url' => $href,
            'title' => $title,
            'add_date' => $addDate ? (int) $addDate : null,
        ];
    }

    private function parseH3(string $html, int $start, int $end): ?string
    {
        $h3Open = stripos($html, '<H3', $start);
        if ($h3Open === false || $h3Open >= $end) {
            return null;
        }

        $h3TagEnd = stripos($html, '>', $h3Open);
        if ($h3TagEnd === false || $h3TagEnd >= $end) {
            return null;
        }

        $h3Close = stripos($html, '</H3>', $h3TagEnd);
        if ($h3Close === false || $h3Close >= $end) {
            return null;
        }

        $name = trim(substr($html, $h3TagEnd + 1, $h3Close - $h3TagEnd - 1));

        return html_entity_decode($name, ENT_QUOTES, 'UTF-8');
    }

    private function extractAttribute(string $tag, string $attr): ?string
    {
        // Match attr="value" or attr='value'
        $pattern = '/\s'.$attr.'\s*=\s*(["\'])(.*?)\1/i';

        if (preg_match($pattern, $tag, $matches)) {
            return html_entity_decode($matches[2], ENT_QUOTES, 'UTF-8');
        }

        return null;
    }

    private function skipWhitespace(string $html, int $pos, int $end): int
    {
        while ($pos < $end && ctype_space($html[$pos] ?? '')) {
            $pos++;
        }

        return $pos;
    }

    private function getNextTag(string $html, int $start, int $end): ?string
    {
        $pos = $start;
        while ($pos < $end) {
            if ($html[$pos] === '<') {
                $tagStart = $pos + 1;
                // Skip whitespace after <
                while ($tagStart < $end && ctype_space($html[$tagStart] ?? '')) {
                    $tagStart++;
                }
                // Read tag name
                $tagName = '';
                while ($tagStart < $end && ! ctype_space($html[$tagStart] ?? '') && $html[$tagStart] !== '>' && $html[$tagStart] !== '/') {
                    $tagName .= $html[$tagStart];
                    $tagStart++;
                }

                $upper = strtoupper($tagName);

                if ($upper === 'A' || $upper === 'H3') {
                    return $upper;
                }

                $pos = $tagStart;
            } else {
                $pos++;
            }
        }

        return null;
    }

    private function findMatchingDLClose(string $html, int $start, int $end): ?int
    {
        $dlOpen = stripos($html, '<DL>', $start);
        if ($dlOpen === false || $dlOpen >= $end) {
            return null;
        }

        $depth = 0;
        $pos = $dlOpen;

        while ($pos < $end) {
            $nextOpen = stripos($html, '<DL>', $pos + 4);
            $nextClose = stripos($html, '</DL>', $pos + 5);

            if ($nextClose === false || $nextClose >= $end) {
                return null;
            }

            if ($nextOpen !== false && $nextOpen < $nextClose) {
                $depth++;
                $pos = $nextOpen + 4;
            } else {
                if ($depth === 0) {
                    return $nextClose;
                }
                $depth--;
                $pos = $nextClose + 5;
            }
        }

        return null;
    }
}

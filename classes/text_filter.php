<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Main class for the PeerTube filter.
 *
 * @package    filter_peertube
 * @copyright  2026 Dag Klimas
 * @author     Dag Klimas <moodle@dag-klimas.de>
 * @author     Andreas Grabs <moodle@grabs-edv.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_peertube;

/**
 * Text filter class for PeerTube videos.
 *
 * @package    filter_peertube
 * @copyright  2026 Dag Klimas
 * @author     Dag Klimas <moodle@dag-klimas.de>
 * @author     Andreas Grabs <moodle@grabs-edv.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class text_filter extends \core_filters\text_filter {
    /**
     * Filters the text and replaces PeerTube links from allowed sources with iFrames via Mustache.
     *
     * @param string $text The text to be filtered.
     * @param array $options Additional options for the filter.
     * @return string The filtered text.
     */
    #[\Override]
    public function filter($text, array $options = []) {
        // Process only valid strings.
        if (!is_string($text) || empty($text)) {
            return '';
        }

        if (!$sources = $this->get_replace_sources()) {
            return $text;
        }

        // Quick preliminary check whether at least one allowed source exists in the text.
        $found = false;
        foreach ($sources as $source) {
            if (stripos($text, $source) !== false) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            return $text;
        }

        $pattern = $this->get_replace_pattern($sources);
        // Replace the found urls by using the pattern and the "replace_callback" method.
        $newtext = preg_replace_callback($pattern, [$this, 'replace_callback'], $text);

        if ($newtext === null) {
            return $text;
        }

        return $newtext;
    }

    /**
     * Retrieves the list of allowed PeerTube instance sources from plugin configuration.
     *
     * @return array|null Array of allowed PeerTube instance URLs, or null if none configured
     *                    or if the configuration is empty.
     */
    protected function get_replace_sources(): ?array {
        // Retrieve allowed sources from plugin settings.
        $allowedsourcesconfig = get_config('filter_peertube', 'allowedsources');
        if (empty($allowedsourcesconfig)) {
            return null;
        }

        // Split into lines and remove empty lines or whitespace.
        $sources = array_filter(array_map('trim', explode("\n", $allowedsourcesconfig)));
        if (empty($sources)) {
            return null;
        }
        return $sources;
    }

    /**
     * Generates a regex pattern to match and replace PeerTube video URLs.
     *
     * This method creates a dynamic regex pattern based on the allowed source domains.
     * The pattern matches either full <a> HTML tags containing the URL or raw URLs.
     *
     * @param array $sources Array of allowed PeerTube instance domains (without protocol)
     * @return string The regex pattern string to match PeerTube video URLs
     */
    protected function get_replace_pattern(array $sources): string {
        // Dynamically create the regex pattern based on settings.
        $escapedsources = array_map(function ($source) {
            // Remove protocol (http:// or https://) for flexible detection.
            $source = preg_replace('#^https?://#i', '', $source);
            return preg_quote(rtrim($source, '/'), '#');
        }, $sources);

        $sourcesregex = implode('|', $escapedsources);

        // Pattern matches:
        // 1. Complete <a ... href=".../w/ID">...</a> tags (Group 1 = Domain, Group 2 = ID).
        // 2. Raw URLs (Group 3 = Domain, Group 4 = ID, Group 5 = Query params).
        return '#<a\b[^>]*href=["\']?https?://(' . $sourcesregex . ')/w/([A-Za-z0-9_-]+)[^"\']*["\']?[^>]*>.*?</a>|'
        . 'https?://(' . $sourcesregex . ')/w/([A-Za-z0-9_-]+)(\?[^<\s"]*)?#is';
    }

    /**
     * Callback function to replace matched PeerTube video links with embed HTML.
     *
     * This method processes regex matches containing PeerTube video URLs and generates
     * the appropriate embed code using a Mustache template.
     *
     * @param array $matches Regex matches.
     * @return string Rendered HTML embed code for the PeerTube video
     */
    protected function replace_callback(array $matches): string {
        global $OUTPUT;

        // Support both match cases (wrapped in <a> tag vs. raw URL).
        $domain = !empty($matches[1]) ? $matches[1] : $matches[3];
        $id = !empty($matches[2]) ? $matches[2] : $matches[4];

        // Build the embed URL dynamically matching the detected instance.
        $embedurl = 'https://' . $domain . '/videos/embed/' . $id;

        // Prepare data for the Mustache template.
        $data = [
            'embedurl' => $embedurl,
        ];

        return $this->get_html_from_template($data);
    }

    /**
     * Get the html from template using the $data value.
     *
     * @param array $data
     * @return bool|string
     */
    protected function get_html_from_template(array $data): string {
        global $OUTPUT;

        // Render template ('filter_peertube' is the component, 'embed' is the template name).
        return $OUTPUT->render_from_template('filter_peertube/embed', $data);
    }
}

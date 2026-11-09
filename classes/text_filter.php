<?php
// This file is part of Moodle - http://moodle.org/.
//
// See version.php for copyright and license information.

/**
 * Main class for the PeerTube filter.
 *
 * @package    filter_peertube
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_peertube;

defined('MOODLE_INTERNAL') || die();

/**
 * Text filter class for PeerTube videos.
 *
 * @package    filter_peertube
 * @copyright  2026 onwards
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
    public function filter($text, array $options = []) {
        // Process only valid strings.
        if (!is_string($text) || $text === '') {
            return $text;
        }

        // Retrieve allowed sources from plugin settings.
        $allowedsourcesconfig = get_config('filter_peertube', 'allowedsources');
        if (empty($allowedsourcesconfig)) {
            return $text;
        }

        // Split into lines and remove empty lines or whitespace.
        $sources = array_filter(array_map('trim', explode("\n", $allowedsourcesconfig)));
        if (empty($sources)) {
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

        // Dynamically create the regex pattern based on settings.
        $escapedsources = array_map(function($source) {
            // Remove protocol (http:// or https://) for flexible detection.
            $source = preg_replace('#^https?://#i', '', $source);
            return preg_quote(rtrim($source, '/'), '#');
        }, $sources);

        $sourcesregex = implode('|', $escapedsources);
        // Matches URLs in the format: https://<allowed-domain>/w/<video-id>
        $pattern = '#https?://(' . $sourcesregex . ')/w/([A-Za-z0-9_-]+)(\?[^<\s"]*)?#i';

        $callback = function(array $matches): string {
            $domain = $matches[1];
            $id = $matches[2];
            
            // Build the embed URL dynamically matching the detected instance.
            $embedurl = 'https://' . $domain . '/videos/embed/' . $id;

            global $OUTPUT;

            // Prepare data for the Mustache template.
            $data = [
                'embedurl' => $embedurl,
            ];

            // Render template ('filter_peertube' is the component, 'embed' is the template name).
            return $OUTPUT->render_from_template('filter_peertube/embed', $data);
        };

        $newtext = preg_replace_callback($pattern, $callback, $text);

        if ($newtext === null) {
            return $text;
        }

        return $newtext;
    }
}
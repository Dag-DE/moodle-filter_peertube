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
 * Display PeerTube filter settings.
 *
 * @package    filter_peertube
 * @copyright  2026 Dag Klimas
 * @author     Dag Klimas <moodle@dag-klimas.de>
 * @author     Andreas Grabs <moodle@grabs-edv.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $setting = new class (
        'filter_peertube/allowedsources',
        get_string('allowedsourceslist', 'filter_peertube'),
        get_string('allowedsourceslistdesc', 'filter_peertube'),
        '',
    ) extends admin_setting_configtextarea {
        /**
         * Validate data before storage.
         *
         * @param string $data The data to validate.
         * @return mixed true if ok string if error found
         */
        public function validate($data) {
            $sources = array_filter(array_map('trim', explode("\n", $data)));
            if (empty($sources)) {
                return true;
            }
            foreach ($sources as $source) {
                // Predefine the error string.
                $errorstring = get_string('settings_error_invalidhost', 'filter_peertube', $source);

                // Check if the source starts with https:// and is a valid domain name.
                if (!str_starts_with($source, 'https://')) {
                    return $errorstring;
                }
                $host = substr($source, 8);

                if (!filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
                    return $errorstring;
                }
            }
            return true;
        }
    };

    $setting->set_updatedcallback(
        /**
         * Cleans and normalizes the allowed sources configuration.
         *
         * Retrieves the 'allowedsources' setting for the filter_peertube plugin, splits it
         * by newlines, trims whitespace from each entry, and removes empty values. If valid
         * sources remain, they are saved back as a newline-separated string; otherwise the
         * configuration is left unchanged.
         *
         * @return void
         */
        function () {
            $data = get_config('filter_peertube', 'allowedsources');
            $sources = array_filter(array_map('trim', explode("\n", $data)));
            if (empty($sources)) {
                return;
            }
            set_config('allowedsources', implode("\n", $sources), 'filter_peertube');
        }
    );

    $settings->add($setting);
}

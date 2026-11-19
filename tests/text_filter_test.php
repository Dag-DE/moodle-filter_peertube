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

namespace filter_peertube;

/**
 * Unit test for the text_filter
 *
 * @package    filter_peertube
 * @copyright  2026 Dag Klimas
 * @author     Dag Klimas <moodle@dag-klimas.de>
 * @author     Andreas Grabs <moodle@grabs-edv.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \filter_peertube\text_filter
 */
final class text_filter_test extends \advanced_testcase {
    /**
     * Valid URLs for the positive tests of the text filter.
     *
     * @var string[]
     */
    protected const VALID_URLS = [
        'https://example1.com',
        'https://example2.com',
    ];

    /**
     * Invalid URL for negative tests (should not be detected/matched).
     *
     * @var string
     */
    protected const INVALID_URL = 'https://example3.com';

    /**
     * Set up the test.
     *
     * @return void
     */
    protected function setUp(): void {
        global $CFG;

        parent::setUp();

        $this->resetAfterTest();
        $this->setAdminUser();

        set_config('allowedsources', implode("\n", static::VALID_URLS), 'filter_peertube');
    }

    /**
     * Data provider for test_replace_peertube_urls.
     *
     * @return array
     */
    public static function get_replace_peertube_urls_test_cases(): array {
        $testcases = [];
        foreach (static::VALID_URLS as $url) {
            $texts = [
                // Bare URL in plain text.
                'Watch ' . $url . '/w/abc123 now'
                    => 'Watch <iframe src="' . $url . '/videos/embed/abc123"></iframe> now',
                // Anchor tag with the URL as href and as link text.
                '<a href="' . $url . '/w/abc123">' . static::INVALID_URL . '/w/abc123</a>'
                    => '<iframe src="' . $url . '/videos/embed/abc123"></iframe>',
                // Anchor tag with a different link text, the whole tag is replaced.
                '<a href="' . $url . '/w/abc123">Watch this video</a>'
                    => '<iframe src="' . $url . '/videos/embed/abc123"></iframe>',
                // Anchor tag with single quotes.
                "<a href='" . $url . "/w/xyz789'>link</a>"
                    => '<iframe src="' . $url . '/videos/embed/xyz789"></iframe>',
                // Anchor tag with additional attributes before href and a line break in the tag.
                "<a class=\"video\"\n   title=\"PeerTube\"\n   href=\"" . $url . "/w/nl001\">some\ntext</a>"
                    => '<iframe src="' . $url . '/videos/embed/nl001"></iframe>',
                // Anchor tag with a line break inside the link text.
                "<a href=\"" . $url . "/w/nl002\">line one\nline two</a>"
                    => '<iframe src="' . $url . '/videos/embed/nl002"></iframe>',
                // Mixed content with a bare URL and an anchor tag.
                $url . '/w/aaa and <a href="' . $url . '/w/bbb">bbb</a>'
                    => '<iframe src="' . $url
                        . '/videos/embed/aaa"></iframe> and <iframe src="' . $url . '/videos/embed/bbb"></iframe>',
                // Multiple bare URLs.
                $url . '/w/one ' . $url . '/w/two'
                    => '<iframe src="' . $url
                        . '/videos/embed/one"></iframe> <iframe src="' . $url . '/videos/embed/two"></iframe>',
                // URLs of other hosts are not touched.
                'See ' . static::INVALID_URL . '/w/abc123 or ' . static::INVALID_URL . '/w/abc123'
                    => 'See ' . static::INVALID_URL . '/w/abc123 or ' . static::INVALID_URL . '/w/abc123',
                // Text without any PeerTube URL stays unchanged.
                '<p>No video here.</p>'
                    => '<p>No video here.</p>',
                // Anchor tag of another host stays unchanged.
                '<a href="' . static::INVALID_URL . '/w/abc">link</a>'
                    => '<a href="' . static::INVALID_URL . '/w/abc">link</a>',
                // Evil substring.
                '<a href="' . $url . '.evildomain.com/w/abc">link</a>'
                    => '<a href="' . $url . '.evildomain.com/w/abc">link</a>',
            ];
            $testcases += $texts;
        }

        $data = [];
        foreach ($testcases as $testcase => $correctresult) {
            $data[] = [$testcase, $correctresult];
        }
        return $data;
    }

    /**
     * Test the replace_peertube_urls method.
     *
     * @dataProvider get_replace_peertube_urls_test_cases
     * @param string $text
     * @param string $correctresult
     */
    public function test_replace_peertube_urls($text, $correctresult): void {
        $testablefilter = $this->get_testable_text_filter();
        $this->assertEquals($correctresult, $testablefilter->filter($text));
    }

    /**
     * Get a copy of the filter configured for testing.
     *
     * @return \filter_peertube\text_filter
     */
    protected function get_testable_text_filter(): text_filter {
        // Create a dummy class so we can test protected methods!
        return new class extends text_filter {
            /**
             * Dummy constructor for this test only
             */
            public function __construct() {
            }

            /**
             * Override the method to get a simpler html output which is better for testing ;).
             *
             * @param array $data
             * @return bool|string
             */
            protected function get_html_from_template(array $data): string {
                return '<iframe src="' . $data['embedurl'] . '"></iframe>';
            }
        };
    }
}

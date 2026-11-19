# PeerTube filter for Moodle

This Moodle text filter automatically replaces PeerTube video links from **allowed
instances** with an embedded video player (iFrame). Links to all other hosts remain
unchanged plain links.

## Features

- Replaces bare URLs like `https://peertube.example.com/w/abc123` with an embedded player.
- Replaces complete anchor tags (`<a href="https://peertube.example.com/w/abc123">...</a>`)
  including their link text.
- Only instances explicitly configured by the administrator are embedded – all other
  links (including look-alike domains such as `peertube.example.com.attacker.tld`) are
  left untouched.
- Responsive, centered 16:9 embed via a Mustache template.
- The filter is enabled by default after installation.

## Requirements

- Moodle 5.3 or later (see `version.php` for the exact required version)

## Installation

* Unzip and copy the plugin into the folder "filter/peertube.
* Visit admin page to install the plugin.

Further installation instructions can be found on the
"[Installing plugins](http://docs.moodle.org/en/Installing_contributed_modules_or_plugins)" Moodle documentation page.

## Configuration

Go to **Site administration > Plugins > Text filters > PeerTube > Settings** and enter
the allowed PeerTube instances in the **Allowed sources** field, one per line:

```
https://peertube.example.com
https://video.example.org
```

Only full URLs starting with `https://` followed by a valid host name are accepted
(e.g. `https://peertube.domain.xyz`). Invalid entries are rejected with an error message
when saving. If the list is empty, no links are converted.

## Usage

Once configured, simply paste a PeerTube video URL into any Moodle text area where the
filter is active:

```
Watch this video: https://peertube.example.com/w/abc123
```

The link is automatically replaced by the embedded video player when the text is
displayed.

## Privacy

This plugin does not store any personal data. See `classes/privacy/provider.php`
(null provider).

## License

This plugin is free software: you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation, either
version 3 of the License, or (at your option) any later version.

See <http://www.gnu.org/licenses/> for details.

## Authors

- Dag Klimas <moodle@dag-klimas.de>
- Andreas Grabs <moodle@grabs-edv.de>

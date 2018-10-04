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
 * This file contains the version information for the mediagallery submission plugin
 *
 * @package    assignsubmission_mediagallery
 * @copyright  2018 Blackboard Inc. {@link https://www.blackboard.com}
 * @author     Adam Olley <adam.olley@blackboard.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2018100400;
$plugin->requires  = 2013050100;
$plugin->component = 'assignsubmission_mediagallery';
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '2.7.1.2';
$plugin->dependencies = array('mod_mediagallery' => 2014112500);

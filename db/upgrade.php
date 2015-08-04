<?php
/**
 * This file keeps track of upgrades to the Snap block
 *
 * @package   block_snap
 * @copyright 2015 Blake Kidney
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade code for the Snap block.
 *
 * @param int $oldversion
 * @param object $block
 */
function xmldb_block_snap_upgrade($oldversion) {
    global $CFG, $DB;

    // Moodle v2.9.0 release upgrade line.
    // Put any upgrade step following this.

    return true;
}

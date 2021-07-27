<?php

/**
 * Version details.
 *
 * @package    disea_dashboard
 * @author	   Sven
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->libdir/formslib.php");

class remove_and_compare_form extends moodleform {
    
    function definition() {
        global $CFG;
        
        $diagram = $this->_customdata['diagram'];
        
        $mform = $this->_form; // Don't forget the underscore!
        $mform->addElement('hidden', 'diagram', $diagram);
        $mform->setType('diagram', PARAM_INTEGER);
        $mform->setDefault('diagram', $diagram);
        $buttonarray=array();
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('remove','block_disea_dashboard'));
        $mform->addGroup($buttonarray, 'buttonar','',' ',false);
    }                           // Close the function
}                               // Close the class
<?php
class block_disea_dashboard extends block_base {
    
    public function init() {
        $this->title = get_string('disea_dashboard', 'block_disea_dashboard');
        global $DB;
        //$this->instance->defaultregion = BLOCK_POS_LEFT;
        //$DB->update_record('block_instances', $this->instance);
    }
    
    function specialization() {
        global $DB;
        $this->instance->defaultregion = BLOCK_POS_LEFT;
        $DB->update_record('block_instances', $this->instance);
    }
    
    function get_content() {
        global $OUTPUT, $PAGE;
        if($this->content !== null) {
            return $this->content;
        }
        
        $chart2 = new core\chart_bar();
        $numbers = new core\chart_series('numbers', [2, 4, 8, 16, 32, 64, 168]);
        $numbers2 = new core\chart_series('numbers2', [168, 64, 32, 16, 8, 4, 2]);
        $labels2 = array('1', '2', '3', '4', '5', '6', '7');
        $numbers2->set_type(\core\chart_series::TYPE_LINE);
        
        $chart2->add_series($numbers);
        $chart2->add_series($numbers2);
        $chart2->set_labels($labels2);
        $content = $OUTPUT->render_chart($chart2);
        
        $content .= "Actuell Score: " . '94'. '%';
        
        $url = new moodle_url('/blocks/disea_dashboard/dashboard.php', array(
            'id' => $PAGE->course->id
        ));
        $templatecontext = (object) [
            'text' => get_string('more_details', 'block_disea_dashboard'),
            'editurl' => $url
        ];
        $footer = $OUTPUT->render_from_template('block_disea_dashboard/more_details', $templatecontext);
        
        $this->content = new stdClass;
        $this->content->text = $content;
        $this->content->footer = $footer;
        return $this->content;
    }
}
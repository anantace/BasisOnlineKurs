<?php

require_once 'app/controllers/news.php';


class ShowController extends StudipController {

    public $ignore_tabs = array('+', 'Verwaltung', '�bersicht', 'Teilnehmeransicht');		



    public function __construct($dispatcher)
    {
        parent::__construct($dispatcher);
        $this->plugin = $dispatcher->plugin;

    }

    public function before_filter(&$action, &$args) {

        $this->set_layout($GLOBALS['template_factory']->open('layouts/base_without_infobox'));
	 PageLayout::setTitle($GLOBALS['SessSemName']["header_line"]);
	 Navigation::activateItem('/course/mini_course');

	 $this->course = Course::findCurrent();
	 if (!$this->course) {
            throw new CheckObjectException(_('Sie haben kein Objekt gew�hlt.'));
        }

	 $this->course_id = $this->course->id;
	 $this->sem = Seminar::getInstance($this->course_id);
        $sem_class = $GLOBALS['SEM_CLASS'][$GLOBALS['SEM_TYPE'][$this->sem->status]['class']];
        $sem_class || $sem_class = SemClass::getDefaultSemClass();
        $this->studygroup_mode = $SEM_CLASS[$SEM_TYPE[$this->sem->status]["class"]]["studygroup_mode"];

    }

    public function index_action() {
	
		foreach( Navigation::getItem('course') as $tab){
		    if(!in_array($tab->getTitle(), $this->ignore_tabs)){
		    	$block = CourseTab::findOneBySQL('seminar_id = ? AND tab IN (?) ORDER BY position ASC',
                                 array($this->course_id,$tab->getTitle()) );
			if ($block){
		    		$this->tabs[] = array('title' => $tab->getTitle(), 
						 'visible' => strcmp($block->getValue('tn_visible'), "yes") == 0 ? 'checked': ''
					  );
			} else 
			   $this->tabs[] = array('title' => $tab->getTitle(), 
						 'visible' => ''
					  );

		    }
		}
		
	 // Fetch news
	 $this->news = StudipNews::GetNewsByRange($this->course_id, !$this->show_all_news, true);	

	
        // Fetch  votes
        // Load evaluations
        $eval_db = new EvaluationDB();
        $this->evaluations = StudipEvaluation::findMany($eval_db->getEvaluationIDs($this->course_id, EVAL_STATE_ACTIVE));
        $show_votes[] = 'active';
        // Check if we got expired
        if (Request::get('show_expired')) {
            $show_votes[] = 'stopvis';
            if ($this->admin) {
                $this->evaluations = array_merge($this->evaluations, StudipEvaluation::findMany($eval_db->getEvaluationIDs($this->course_id, EVAL_STATE_STOPPED)));
                $show_votes[] = 'stopinvis';
            }
        }

        $this->votes = StudipVote::findBySQL('range_id = ? AND state IN (?) ORDER BY mkdate desc', array($this->course_id,$show_votes));
	
	 if ($vote = Request::get('vote')) {
            $vote = new StudipVote($vote);
            if ($vote && $vote->isRunning() && (!$vote->userVoted() || $vote->changeable)) {
                try {
                    $vote->insertVote(Request::getArray('vote_answers'), $GLOBALS['user']->id);
                } catch (Exception $exc) {
                    $GLOBALS['vote_message'][$vote->id] = MessageBox::error($exc->getMessage());
                }
            }
        }

	$this->description = $this->sem->__get('Beschreibung');

	$response = $this->relay("show/members");
       $this->members = $response->body;
	/**
        // Fetch dates
        if (!$this->studygroup_mode) {
            $response = $this->relay("calendar/contentbox/display/{$this->course_id}/1210000");
            $this->dates = $response->body;
        }
	**/
	$response = $this->relay("show/documents");
	$this->documents = $response->body;

    }

    public function save_action() {
	
	$this->tabs = $_POST;
	$tab_count = intval($this->tabs['tab_num']);

	for ($i = 0; $i < $tab_count - 1; $i++){

		$block = new CourseTab();
		
		//falls noch kein Eintrag existiert: anlegen
		if (!CourseTab::findOneBySQL('seminar_id = ? AND tab IN (?) ORDER BY position ASC',
                                 array($this->course_id,$this->tabs['tab_title_'. $i]))){
			$block->setData(array(
            		'seminar_id' => $this->course_id,
           		'tab'       => $this->tabs['tab_title_'. $i],
            		'tn_visible'      => $this->tabs['visible_'. $i] == 'on' ? 'yes' : 'no'
        		));	

        		$block->store();
		} 

		//falls ein Eintrag existiert: anpassen
		else {
			$block = CourseTab::findOneBySQL('seminar_id = ? AND tab IN (?) ORDER BY position ASC',
                                 array($this->course_id,$this->tabs['tab_title_'. $i]));
			var_dump($block->getValue('tab'));
			var_dump($block->getValue('title'));
			//var_dump($block[0]['tn_visible']);
			//var_dump($block[0]['position']);
			$block->setValue('tn_visible', $this->tabs['visible_'. $i] == 'on' ? 'yes' : 'no');
			//$block->setValue('title', 'yes');
			$block->store();

		}
	}


    }
	
    public function overview_action() {
		
    }

    public function members_action() {
	$this->dozenten = $this->sem->getMembers('dozent');
	$this->tutoren = $this->sem->getMembers('tutor');
	$this->autoren = $this->sem->getMembers('autor');
	$this->users = $this->sem->getMembers('user');
    }

    public function documents_action() {
	$this->documents = $this->getSeminarDocuments();

    }  
  
    public function showResult($vote) {
        if (Request::submitted('change') && $vote->changeable) {
            return false;
        }
        return $vote->userVoted() || in_array($vote->id, Request::getArray('preview'));
    }

    private function getSeminarDocuments(){
	// Dokumente 
 	$query = "SELECT dokument_id, filename, description, name FROM dokumente WHERE seminar_id='{$this->sem->getId()}'"; 
        
	$documents;    
 	$l = 0; 
 	$statement = DBManager::get()->prepare($query); 
 	$statement->execute(); 

 	while ($row = $statement->fetch(PDO::FETCH_ASSOC)) { 

 		if ($row['name']){ 
 			$documents[$l]['DOCUMENT_TITLE'] = htmlReady($row['name']); 
 	       } 
		if ($row['filename']){ 
 			$documents[$l]['DOCUMENT_FILENAME'] = htmlReady($row['filename']); 
 	       } 

 	 	if ($row['dokument_id']){ 
 			$documents[$l]['DOCUMENT_ID'] = htmlReady($row['dokument_id']); 
 			//$content['LECTUREDETAILS']['DOCUMENTS']['DOCUMENT'][$l]['DOCUMENT_DOWNLOAD_URL'] = ExternModule::ExtHtmlReady($this->$db->f('file_id')); 
 		} 
		if ($row['description']){ 
 			$documents[$l]['DOCUMENT_DESCRIPTION'] = htmlReady($row['description']); 
 	       }
 	$l++; 
 	} 
	return $documents;
    }
    
	
	function url_for($to)
    {
        $args = func_get_args();

        # find params
        $params = array();
        if (is_array(end($args))) {
            $params = array_pop($args);
        }

        # urlencode all but the first argument
        $args = array_map('urlencode', $args);
        $args[0] = $to;

        return PluginEngine::getURL($this->dispatcher->plugin, $params, join('/', $args));
    } 

}

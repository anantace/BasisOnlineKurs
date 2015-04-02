<?php

require_once 'app/controllers/news.php';


class ShowController extends StudipController {

    public $group_licenses = false;		



    public function __construct($dispatcher)
    {
        parent::__construct($dispatcher);
        $this->plugin = $dispatcher->plugin;

    }

    public function before_filter(&$action, &$args) {

        $this->set_layout($GLOBALS['template_factory']->open('layouts/base_without_infobox'));
	 PageLayout::setTitle($GLOBALS['SessSemName']["header_line"]);

	 $this->course = Course::findCurrent();
	 if (!$this->course) {
            throw new CheckObjectException(_('Sie haben kein Objekt gewählt.'));
        }

	 $this->course_id = $this->course->id;
	 $this->sem = Seminar::getInstance($this->course_id);
        $sem_class = $GLOBALS['SEM_CLASS'][$GLOBALS['SEM_TYPE'][$this->sem->status]['class']];
        $sem_class || $sem_class = SemClass::getDefaultSemClass();
        $this->studygroup_mode = $SEM_CLASS[$SEM_TYPE[$this->sem->status]["class"]]["studygroup_mode"];

    }

    public function index_action() {
	
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
    

}

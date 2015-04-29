<?php


/**
 * MiniCourse.class.php
 *
 * ...
 *
 * @author  <asudau@uos.de>
 */

require_once 'lib/classes/DBManager.class.php';

class MiniCourse extends StudIPPlugin implements SystemPlugin
{
    /**
     * @var Container
     */
    private $container;

    

    public function __construct() {
        parent::__construct();

		global $perm;
		$this->setupStudIPNavigation();
		$referer = $_SERVER['REQUEST_URI'];

		if ($this->isMiniCourse($this->getSeminarId()) ){

		    if(!$perm->have_studip_perm('tutor', $this->getSeminarId()) ) {
 			if($referer!=str_replace("/course/overview","",$referer)){
				header('Location: http://192.168.56.101/el4/vhs-3.1/public/plugins.php/minicourse/show?cid=' . $this->getSeminarId(), true, 303);
				exit();	
			} 
		    }
			
		    else if ( !$perm->have_perm('admin') )
		    {
			var_dump($perm->get_perm());
			$this->setupMiniCourseNavigation();	
		    }
		    
	  	} else if ( ( ($referer!=str_replace("dispatch.php/start","",$referer)) ||   ($referer!=str_replace("dispatch.php/my_courses","",$referer))   ) && ($this->isSingleMiniCourseUser($GLOBALS['user']->id)) ){
				
				$result = $this->getSemStmt($GLOBALS['user']->id);

				header('Location: http://192.168.56.101/el4/vhs-3.1/public/plugins.php/minicourse/show?cid='. $result['seminar_id'], true, 303);
				exit();	
			} 

    }

    // bei Aufruf des Plugins Ã¼ber plugin.php/mooc/...
    public function initialize ()
    {
        //PageLayout::addStylesheet($this->getPluginUrl() . '/css/style.css');
        //PageLayout::addStylesheet($this->getPluginURL().'/assets/style.css');
        //PageLayout::addScript($this->getPluginURL().'/assets/application.js');
	 
    }
	
    public function perform($unconsumed_path) {

	 $this->setupAutoload();
        $dispatcher = new Trails_Dispatcher(
            $this->getPluginPath(),
            rtrim(PluginEngine::getLink($this, array(), null), '/'),
            'show'
        );
        $dispatcher->plugin = $this;
        $dispatcher->dispatch($unconsumed_path);

	 
    }

    private function setupAutoload() {
        if (class_exists("StudipAutoloader")) {
            StudipAutoloader::addAutoloadPath(__DIR__ . '/models');
        } else {
            spl_autoload_register(function ($class) {
                include_once __DIR__ . $class . '.php';
            });
        }
    }

    static function getSeminarId()
    {
        if (!Request::option('cid')) {
            if ($GLOBALS['SessionSeminar']) {
                URLHelper::bindLinkParam('cid', $GLOBALS['SessionSeminar']);
                return $GLOBALS['SessionSeminar'];
            }

            return false;
        }

        return Request::option('cid');
    }


    private function getSemClass($semID)
    {
        try{
			$seminar = Seminar::getInstance($semID);
			$status = $seminar->getStatus();
			$type = new SemType(intval($status));
			$class = SemClass::object2Array($type->getClass());
			return $class['data']['id'];
		}
		catch (Exception $e) {
			return NULL;
		}
	 
    }


    private function isMiniCourse($semID)
    {
        if ($this->getSemClass($semID) == Config::get()->getValue('MiniCourse_SEM_CLASS_CONFIG_ID')) {
            return true;
        }
        return false;
    }

    private function isSingleMiniCourseUser($user_id)
    {
        $stmt = $this->getSemStmt($user_id);
	 if ($this->isMiniCourse($stmt['seminar_id'])){
		return true;
	 } else return false;
	 
    }

    private function getSemCount($user_id){
	 
	 $stmt = DBManager::get()->prepare("SELECT su.seminar_id FROM seminar_user su
					WHERE su.user_id = ?");
	 $stmt->execute(array($user_id));
	 $count = $stmt->rowCount();
	 return $count;
    }

    private function getSemStmt($user_id){
	
	   $stmt = DBManager::get()->prepare("SELECT su.seminar_id FROM seminar_user su
					WHERE su.user_id = ?");
	   $stmt->execute(array($user_id));
	   $count = $stmt->rowCount();
	   if($count == 1){
	   	return $stmt->fetch();
	   }
	   else return false;
    }

    private function getMiniCourseNavigation($title)
    {

        $navigation = new Navigation($title, PluginEngine::getURL($this));
        $navigation->setImage('icons/16/white/group3.png');
        $navigation->setActiveImage('icons/16/black/group3.png');

        return $navigation;
    }

    private function setupStudIPNavigation(){
	 

	 global $perm;

	 $count = $this->getSemCount($GLOBALS['user']->id);
	 if($count == 1){
	 	$result = $this->getSemStmt($GLOBALS['user']->id);
		
		//If User is member in only one course which is a miniCourse
		if ($this->isSingleMiniCourseUser($GLOBALS['user']->id)){

			//MiniCourse Navigation for Autor
			if(!$perm->have_studip_perm('tutor', $result['seminar_id'] )){
        		
				//$this->setupMiniCourseNavigation();
				
				//StudIP Navigation for MiniCourse-User
				if (Navigation::hasItem('/community')) {
					Navigation::removeItem('/community');
        		}
				if (Navigation::hasItem('/calendar')) {
					Navigation::removeItem('/calendar');
        		}
				if (Navigation::hasItem('/start')) {
					Navigation::removeItem('/start');
        		}

			}
			

			//for every user with only one MiniCourse
			if (Navigation::hasItem('/start/my_courses')) {
					Navigation::getItem('/start/my_courses')->setURL("/plugins.php/minicourse/show?cid=". $result['seminar_id']);
					Navigation::getItem('/start/my_courses')->setTitle("Mein Kurs");
					//Navigation::removeItem('/start');
        		}

			if (Navigation::hasItem('/course')){
				Navigation::getItem('/course')->setURL("/plugins.php/minicourse/show?cid=". $result['seminar_id']);
				Navigation::getItem('/course')->setTitle("Mein Kurs");
			}

			Navigation::getItem('/browse')->setURL("/plugins.php/minicourse/show?cid=". $result['seminar_id']);
			Navigation::getItem('/browse')->setTitle("Mein Kurs");
			
			

		} else {
			//Only one, but ordinary course
			Navigation::getItem('/browse')->setURL("/seminar_main.php?auswahl=". $result['seminar_id']);
			Navigation::getItem('/browse')->setTitle("Mein Kurs");
			if (Navigation::hasItem('/course')){
				Navigation::getItem('/course')->setURL("/seminar_main.php?auswahl=". $result['seminar_id']);
				Navigation::getItem('/course')->setTitle("Mein Kurs");
			}
		}

	 }
	 else if($count == 0){
			if(!$perm->have_perm('tutor')){
				Navigation::removeItem('/browse');
			} 
			
	 }
	 else if($count > 1){
		if ($this->isMiniCourse($this->getSeminarId())){
			//$this->setupMiniCourseNavigation();
	 	}
	 }
	 else if ($this->isMiniCourse($this->getSeminarId())){
				//$this->setupMiniCourseNavigation();
	 	 		
	  	}

	}
	 	
		/**fÃ¼r Admin - zurzeit Ã¼berflÃ¼ssig
		if( ( $this->isMiniCourse($this->getSeminarID()) ) && ($my_about->auth_user['perms'] == 'dozent') ) {
	 			if (Navigation::hasItem('/course')){
        			
				if($this->getContext()){
					/** @var Navigation $courseNavigation 
        				$courseNavigation = Navigation::getItem('/course');
        				//$overviewNavigation = $courseNavigation::getItem('/course/overview');
					$it = $courseNavigation->getIterator();

        				Navigation::insertItem('/course/mini_course', $this->getMiniCourseNavigation("Teilnehmeransicht"), $it->count() === 0 ? null : $it->key());
            				//Navigation::activateItem('/course/mini_course');
				}
				
			}
	 	
	 	}
	 }**/
	
	
	private function setupMiniCourseNavigation(){
		global $perm;
		if(!$perm->have_studip_perm('tutor', $this->getSeminarId() )){
		   if (Navigation::hasItem('/course')){
			if (Navigation::hasItem('/course/members')) {   //&& !$perm->have_perm('tutor')
				Navigation::removeItem('/course/members');
			}
			if (Navigation::hasItem('/course/main')) {
				Navigation::removeItem('/course/main');
			}
			if (Navigation::hasItem('/course/forum2')) {
				Navigation::removeItem('/course/forum2');
			}
			if (Navigation::hasItem('/course/files')) {
				Navigation::removeItem('/course/files');
			}
			if (Navigation::hasItem('/course/schedule')) {
				Navigation::removeItem('/course/schedule');
			}
			if (Navigation::hasItem('/course/scm')) {
				Navigation::removeItem('/course/scm');
        		}
				
		     $courseNavigation = Navigation::getItem('/course');
        		
		     $it = $courseNavigation->getIterator();
		     Navigation::insertItem('/course/mini_course', $this->getMiniCourseNavigation("Mein Kurs"), $it->count() === 0 ? null : $it->key());
            	     Navigation::activateItem('/course/mini_course');
		   }
		} else {
		   if (Navigation::hasItem('/course')){
			$courseNavigation = Navigation::getItem('/course');
        		
		       $it = $courseNavigation->getIterator();
		       Navigation::insertItem('/course/mini_course', $this->getMiniCourseNavigation("Teilnehmeransicht"), $it->count() === 0 ? null : $it->key());
            	       Navigation::activateItem('/course/mini_course');
		   }
		}
			
	}
	
   
    public function getInfoTemplate($course_id){
	return null;
    }
    public function getIconNavigation($course_id, $last_visit, $user_id){
	return null;
    }
    public function getTabNavigation($course_id){
	return null;
    }
    function getNotificationObjects($course_id, $since, $user_id){
    }

    public function getContext()
    {
        return Request::option('cid') ?: $GLOBALS['SessionSeminar'];
    }

   
}

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
	

	$this->setupCourseNavigation();
	
    }

    // bei Aufruf des Plugins über plugin.php/mooc/...
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

    private function getMiniCourseNavigation($title)
    {

        $navigation = new Navigation($title, PluginEngine::getURL($this));
        $navigation->setImage('icons/16/white/group3.png');
        $navigation->setActiveImage('icons/16/black/group3.png');

        return $navigation;
    }

    private function setupCourseNavigation(){
	 
	 global $perm;
	 $stmt = DBManager::get()->prepare("SELECT su.seminar_id FROM seminar_user su
					WHERE su.user_id = ?");
	 $stmt->execute(array($GLOBALS['user']->id));
	 $count = $stmt->rowCount();
	 if($count == 1){
	 	$result = $stmt->fetch();
		if ($this->isMiniCourse($result['seminar_id'])){

			//MiniCourse Navigation for Autor
			if(!$perm->have_studip_perm('tutor', $result['seminar_id'] )){
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
				if (Navigation::hasItem('/community')) {
				Navigation::removeItem('/community');
        			}
				if (Navigation::hasItem('/start/my_courses')) {
					Navigation::getItem('/start/my_courses')->setURL("/plugins.php/minicourse/show?cid=". $result['seminar_id']);
					Navigation::getItem('/start/my_courses')->setTitle("Mein Kurs");
				//Navigation::removeItem('/start');
        			}
				if (Navigation::hasItem('/calendar')) {
				Navigation::removeItem('/calendar');
        			}

			}
	
			if (Navigation::hasItem('/course')){
        			
				if($this->getContext()){
					/** @var Navigation $courseNavigation */
        				$courseNavigation = Navigation::getItem('/course');
        				//$overviewNavigation = $courseNavigation::getItem('/course/overview');
					$it = $courseNavigation->getIterator();

        				Navigation::insertItem('/course/mini_course', $this->getMiniCourseNavigation("Mein Kurs"), $it->count() === 0 ? null : $it->key());
            				Navigation::activateItem('/course/mini_course');
				}
				Navigation::getItem('/course')->setURL("/plugins.php/minicourse/show?cid=". $result['seminar_id']);
				Navigation::getItem('/course')->setTitle("Mein Kurs");
				
			}

			Navigation::getItem('/browse')->setURL("/plugins.php/minicourse/show?cid=". $result['seminar_id']);
			Navigation::getItem('/browse')->setTitle("Mein Kurs");
			if (Navigation::hasItem('/start/my_courses')){
				Navigation::getItem('/start/my_courses')->setURL("/plugins.php/minicourse/show?cid=". $result['seminar_id']);
				Navigation::getItem('/start/my_courses')->setTitle("Mein Kurs");
			}
			

		} else {

			Navigation::getItem('/browse')->setURL("/seminar_main.php?auswahl=". $result['seminar_id']);
			Navigation::getItem('/browse')->setTitle("Mein Kurs");
			if (Navigation::hasItem('/course')){
				Navigation::getItem('/course')->setURL("/seminar_main.php?auswahl=". $result['seminar_id']);
				Navigation::getItem('/course')->setTitle("Mein Kurs");
			}
		}

	 }
	 if($count == 0){
	 	
	 	if($my_about->auth_user['perms'] == 'autor'){
			Navigation::removeItem('/browse');
	 	}
	 	
		//für Admin - zurzeit überflüssig
		if( ( $this->isMiniCourse($this->getSeminarID()) ) && ($my_about->auth_user['perms'] == 'dozent') ) {
	 			if (Navigation::hasItem('/course')){
        			
				if($this->getContext()){
					/** @var Navigation $courseNavigation */
        				$courseNavigation = Navigation::getItem('/course');
        				//$overviewNavigation = $courseNavigation::getItem('/course/overview');
					$it = $courseNavigation->getIterator();

        				Navigation::insertItem('/course/mini_course', $this->getMiniCourseNavigation("Teilnehmeransicht"), $it->count() === 0 ? null : $it->key());
            				//Navigation::activateItem('/course/mini_course');
				}
				
			}
	 	
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

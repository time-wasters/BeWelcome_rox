<?php
/*

Copyright (c) 2007 BeVolunteer

This file is part of BW Rox.

BW Rox is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

BW Rox is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, see <http://www.gnu.org/licenses/> or
write to the Free Software Foundation, Inc., 59 Temple Place - Suite 330,
Boston, MA  02111-1307, USA.

*/

use Carbon\Carbon;

/**
 * signup model
 *
 * @package signup
 * @author Felix van Hove <fvanhove@gmx.de>
 */
class SignupModel extends RoxModelBase
{
    /**
     * PERL regular expression for handles
     */

    /**
     * user
     * TODO: should get a more specific name - refactoring needed!
     */
    // Allow usernames with up to 20 chars for new signup. Allow ., - and _. Don't allow consecutive special chars.
    const PATTERN_USERNAME = '[A-Za-z](?!.*[-_.][-_.])[A-Za-z0-9-._]{2,18}[A-Za-z0-9]';
    const HANDLE_PREGEXP = '/^[A-Za-z](?!.*[-_.][-_.])[A-Za-z0-9-._]{2,18}[A-Za-z0-9]$/';

    /**
     * FIXME: use BW constant from config file instead of this one
     */
    const YOUNGEST_MEMBER = 18;

    const BW_TRUE = 'Yes';
    const BW_FALSE = 'No';

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * confirmation process
     *
     * @param string $handle
     * @param string $key
     * @return boolean
     */
    public function confirmRegister($handle, $key)
    {
        $userId = APP_User::userId($handle);
        if (!$userId)
            return false;
        $keyDB = APP_User::getSetting($userId, 'regkey');
        if (!$keyDB)
            return false;
        if ($keyDB->value != $key)
            return false;
        APP_User::activate($userId);
        return true;
    }

    /**
     * checks if e-mail address is in use
     *
     * @param string $email
     * @return boolean
     */
    public function emailInUse($email)
    {
/*
        $query = '
SELECT `id`
FROM `user`
WHERE `email` = \''.$this->dao->escape(strtolower($email)).'\'';
*/
        $query = '
SELECT `id`
FROM `members`
WHERE `Email` = \'' . $this->dao->escape(strtolower($email)).'\'';

        $s = $this->dao->query($query);
        if (!$s) {    // TODO: always integrate this check?
            throw new PException('Could not determine if email is in use!');
        }

        return $s->numRows();
    } // end of emailInUse

    /**
     * Determine other users (plural!), who use the same
     * e-mail address, then
     * - add this fact to the feedback text and
     * - write this fact to the log
     *
     * @param string $email lower case e-mail address
     * @return string text to be added to feedback text, in
     * 				  case of no hit ''
     */
    public function takeCareForNonUniqueEmailAddress($email)
    {
        $email = $this->dao->escape($email);

        // Thanks to the messed up database we need to check more than just the DB column
        // First try with the 'real' email address and a plain entry in AdminCryptedValue
        // Second try with the 'real' email address and an 'XML' entry in AdminCryptedValue
        // And finally check for a 'URL encoded' email address only happens with an 'XML' entry
        $query = "
            SELECT `Username`, members.`Status`, members.`id` AS `idMember`
            FROM " . PVars::getObj('syshcvol')->Crypted . "`cryptedfields`
            RIGHT JOIN `members` ON members.`id` = cryptedfields.`IdMember`";
        if ($this->_session->has( 'IdMember' )) {
            $query .= ' AND members.`id`!=' . $this->_session->get('IdMember');
        }
        $query .= " WHERE (`AdminCryptedValue` = '" . $email . "'";
        $query .= " OR `AdminCryptedValue` = '<admincrypted>" . $email . "</admincrypted>'";
        $email = str_replace("@", "%40", $email);
        $query .= " OR `AdminCryptedValue` = '<admincrypted>" . $email . "</admincrypted>')";
        $s = $this->dao->query($query);
        $found = ($s->numRows() != 0);
        if (!$found) {
			if (!empty($email)) MOD_log::get()->write("Unique email checking done successfuly","Signup") ;
            return '';
        }
        $text = "Non unique email address: " . $email . " (With New Signup !)";
		MOD_log::get()->write($text, "Signup");
		return $text;
    } // end takeCareForNonUniqueEmailAddress

    /**
     * Check, if computer has previously been used by BW member
     *
     * (If signup team wanna get nicer e-mails, we'll provide adequate
     * functionalities via signup.view.php and a template.)
     *
     * TODO: I wonder, why BW signup team cares for my box; member Bin L.
     * has been logged in before at this computer, - should be nothing to them.
     *
     * @return string text (not HTML) to be added to feedback text, in
     * 				  case of no cookie ''
     */
    public function takeCareForComputerUsedByBWMember()
    {
        if (isset($_COOKIE['MyBWusername'])) {
            $text = 'takeCareForComputerUsedByBWMember: This user had previously been logged in as a BW member ' .
                    'at the same computer, which has been used for ' .
                    'registration: ' . $_COOKIE['MyBWusername'];
			MOD_log::get()->write($text." (With New Signup !)", "Signup");
			return $text;
        }
				MOD_log::get()->write("takeCareForComputerUsedByBWMember: Seems never used before"." (With New Signup !)", "Signup");

        return '';
    } // takeCareForComputerUsedByBWMember

    public function find($str)
    {
    	if (!preg_match(self::HANDLE_PREGEXP, $str))
            return 'format';
        $query = '
SELECT
    u.`id`,
    u.`handle`,
    COUNT(f.`user_id_foreign`) AS `is_friend`
FROM `user` AS u
LEFT JOIN `user_friends` AS f ON
    f.`user_id_foreign` = '.(APP_User::login() ? (int)APP_User::login()->getId() : 'null').'
    AND
    f.`user_id_foreign_friend` = u.`id`
WHERE
    u.`handle` LIKE \'%'.$this->dao->escape($str).'%\'
GROUP BY u.`id`
ORDER BY u.`handle`
        ';
        $q = $this->dao->query($query);
        if (!$q->numRows())
            return false;
        return $q;
    }

    /**
     * returns handle as written in DB
     */
    public function getRealHandle($userId)
    {
        $s = $this->dao->query('SELECT `handle` FROM `user` WHERE `id` = '.(int)$userId);
        if ($s->numRows() != 1)
            return false;
        return $s->fetch(PDB::FETCH_OBJ)->handle;
    }

    /**
     * Get all user fields
     *
     * @param int $userId
     * @return stdClass
     */
    public function getUser($userId)
    {
        $query = '
SELECT
    `id`,
    `handle`,
    `auth_id`,
    `email`,
    `active`,
    `lastlogin`
FROM `user` WHERE
    `id` = '.(int)$userId.'
        ';
        $s = $this->dao->query($query);
        if ($s->numRows() == 0)
            return false;
        if ($s->numRows() != 1)
            throw new PException('Data inconsistency');
        return $s->fetch(PDB::FETCH_OBJ);
    }

    /**
     * returns "true" if Username is in use (in members or in members who have quitted)
     *
     * @param string $Username
     * @return boolean true if username was used before, false if not
     */
    public function UsernameInUse($Username)
    {
        $query = 'SELECT `id` FROM `members` WHERE `Username` = \''.$this->dao->escape(strtolower($Username)).'\'';
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('Could not determine if Username is in use in members!');
        }
        $row = $s->fetch(PDB::FETCH_OBJ);
        if (isset($row->id)) {
            return(true) ; // found a still used Username
        }

/*      \todo shevek: Remove this part of code as old usernames are no longer recorded
        $query = 'SELECT `UsernameNotToUse` FROM `recorded_usernames_of_left_members` WHERE `UsernameNotToUse` = \''.
				$this->dao->escape(strtolower($Username)).'\'';
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('Could not determine if Username is in use in recorded_usernames_of_left_members!');
        }
        $row = $s->fetch(PDB::FETCH_OBJ);
        if (isset($row->UsernameNotToUse)) {
            return(true); // found an ex used Username
        }
*/
        return(false);
    } // end of UsernameInUse

    /**
     * Processing registration
     *
     * This is a POST callback function
     *
     * @see /htdocs/bw/signup.php
     * @param void
     */
    public function registerProcess()
    {
        $c = PFunctions::hex2base64(sha1(__METHOD__));
        if (PPostHandler::isHandling()) {
            $vars =& PPostHandler::getVars();
            $errors = $this->checkRegistrationForm($vars);
            if (count($errors) > 0) {
                $vars['errors'] = $errors;
                return false;
            }

            $this->polishFormValues($vars);

            $idTB = $this->registerTBMember($vars);
            if (!$idTB) {
                MOD_log::get()->write("TB registration failed","Signup") ;
                return false;
            }

            $id = $this->registerBWMember($vars);
            $this->_session->set( 'IdMember', $id );

            $vars['feedback'] .= $this->takeCareForNonUniqueEmailAddress($vars['email']);
            $vars['feedback'] .= $this->takeCareForComputerUsedByBWMember();

            $this->writeFeedback($vars['feedback']);
			if (!empty($vars['feedback'])) {
				MOD_log::get()->write("feedback[<b>".stripslashes($vars['feedback'])."</b>] IdMember=#".$this->_session->get('IdMember')." (With New Signup !)","Signup");
			}

            $View = new SignupView($this);
            // TODO: BW 2007-08-19: $_SYSHCVOL['EmailDomainName']
            define('DOMAIN_MESSAGE_ID', 'bewelcome.org');    // TODO: config
            $View->registerMail($vars, $id,$idTB);
            return PVars::getObj('env')->baseuri.'signup/register/finish';
        } else {
            PPostHandler::setCallback($c, __CLASS__, __FUNCTION__);
            return $c;
        }
    }

    /**
     * TODO: use a language column with character codes
     * instead of integers (!?)
     */
    public function writeFeedback($feedback)
    {
        if (!empty($feedback)) {
            define('FEEDBACK_CATEGORY_SIGNUP', 3);
            $lang = $this->determineLangInteger();
            $query = '
INSERT INTO
	`feedbacks`
(
	`created`,
	`Discussion`,
	`IdFeedbackCategory`,
	`IdVolunteer`,
	`Status`,
	`IdLanguage`,
	`IdMember`
)
VALUES(
	now(),
	\'' . $feedback . '\',
	' . FEEDBACK_CATEGORY_SIGNUP . ',
	0,
	\'closed by member\',
	' . $lang . ',
	' . $this->_session->get('IdMember') . '
)';
            $s = $this->dao->query($query);
        }
    }

    private function determineLangInteger()
    {
        $query = '
SELECT `id`
FROM `languages`
WHERE `ShortCode` = \'' . $this->_session->get('lang') . '\'';
        $q = $this->dao->query($query);
        $result = $q->fetch(PDB::FETCH_OBJ);
        return $result->id;
    }

    public function registerTBMember($vars)
    {
        $Auth = new MOD_bw_user_Auth;
        $authId = $Auth->checkAuth('defaultUser');

        $query = '
INSERT INTO `user`
(`auth_id`, `handle`, `email`, `active`)
VALUES
(
    '.(int)$authId.',
    \'' . $vars['username'] . '\',
    \'' . $vars['email'] . '\',
    0
)';
        $s = $this->dao->query($query);
        if (!$s->insertId()) {
            $vars['errors'] = array('inserror');
            return false;
        }
        $userId = $s->insertId();
        $key = PFunctions::randomString(16);
        // save register key
        if (!APP_User::addSetting($userId, 'regkey', $key)) {
            $vars['errors'] = array('inserror');
            return false;
        }
        // save lang
        if (!APP_User::addSetting($userId, 'lang', PVars::get()->lang)) {
            $vars['errors'] = array('inserror');
            return false;
        }

        return $userId;
    }

    /**
     *
     * This has NOT been executed:
     * ALTER TABLE members
     * MODIFY COLUMN `id` int( 11 ) NOT NULL COMMENT 'IdMember'
     * As a result, we do NOT use
     * '.$this->dao->nextId('members').',
     * @param $vars
     * @return bool
     * @throws EntityException
     * @throws PException
     */
    public function registerBWMember($vars)     {
        // ********************************************************************
        // members
        // ********************************************************************
        $query = "
            INSERT INTO `members`
            (
                `Username`,
                `IdCity`,
                `Latitude`,
                `Longitude`,
                `Gender`,
                `HideGender`,
                `created`,
                `BirthDate`,
                `HideBirthDate`,
                `MaxGuest`,
                `Email`,
                `FirstName`,
                `SecondName`,
                `LastName`,
                `HideAttribute`
            )
            VALUES
            ( ?, ?, ?, ?, ?, ?, NOW(), ?, ?, 1, ?, ?, ?, ?, ? );";
        $stmt = $this->dao->prepare($query);
        $stmt->bindParam(0, $vars['username']);
        $stmt->bindParam(1, $vars['location-geoname-id']);
        $stmt->bindParam(2, $vars['location-latitude']);
        $stmt->bindParam(3, $vars['location-longitude']);
        $stmt->bindParam(4, $vars['gender']);
        $stmt->bindParam(5, $vars['genderhidden']);
        $stmt->bindParam(6, $vars['iso_date']);
        $stmt->bindParam(7, $vars['agehidden']);
        $stmt->bindParam(8, $vars['email']);
        $stmt->bindParam(9, $vars['firstname']);
        $stmt->bindParam(10, $vars['secondname']);
        $stmt->bindParam(11, $vars['lastname']);
        $hide = \Member::MEMBER_All_HIDDEN;
        $stmt->bindParam(12, $hide);

        $res = $stmt->execute();
        $memberID = $stmt->insertId();

        $memberEntity = new Member($memberID);
        $vars['password'] = $memberEntity->preparePassword($vars['password']);
        $motherTongue = $this->createEntity('Language', $vars['mothertongue']);
        $memberLanguageEntity = $this->createEntity('MemberLanguage');
        $memberLanguageEntity->setSpokenLanguage($memberEntity, $motherTongue, 'MotherLanguage');

        // Check if selected MotherTongue is in the list of translated languages and set it as preferred language
        $model = new FlaglistModel();
        $languages = $model->getLanguages();

        $languageFilter = function ($lang)
        {
            return function ($v) use ($lang) { return $v->id == $lang; };
        };

        $update="INSERT INTO memberspreferences (IdMember, IdPreference, Value) VALUES ";
        $filteredLanguages = array_filter($languages, $languageFilter($motherTongue->id));
        if (!empty($filteredLanguages)) {
            // \todo Remove hard coded pointer into preference table
            $update .= "($memberEntity->id, 1, " . $motherTongue->id . ")," . PHP_EOL;
        }

        // Set newsletter preference
        // \todo Remove hard coded pointer into preference table
        $update .= "($memberEntity->id, 8, '" . $vars['newsletters'] . "'), " . PHP_EOL;

        // Set local info preference
        // \todo Remove hard coded pointer into preference table
        $update .= "($memberEntity->id, 13, '" . $vars['local-info'] . "')" . PHP_EOL;

        $this->dao->query($update);

        $memberEntity->update();
        $memberEntity->setPassword($vars['password']);

        // ********************************************************************
        // address/addresses
        // ********************************************************************
        $query = '
INSERT INTO addresses
(
	`IdMember`,
	`IdCity`,
	`HouseNumber`,
	`StreetName`,
	`Zip`,
	`created`,
	`Explanation`
)
VALUES
(
	' . $memberID . ',
	' . $vars['location-geoname-id'] . ',
    0,
	0,
	0,
	now(),
	0)';
        $s = $this->dao->query($query);
        if( !$s->insertId()) {
            $vars['errors'] = array('inserror');
            return false;
        }

		MOD_log::get()->writeIdMember($memberID,"member  <b>".$vars['username']."</b> is signuping with success in city [".$vars['location']. "]  using language (".$this->_session->get("lang")." IdMember=#".$memberID." (With New Signup !)","Signup");

        return $memberID;

    }

    /**
     * $vars is required to contain an e-mail
     */
    public function polishFormValues(&$vars)
    {
        if (!(isset($vars['agehidden']) &&
                strcmp($vars['agehidden'], self::BW_TRUE) == 0)) {
            $vars['agehidden'] = self::BW_FALSE;
        }

        if (!(isset($vars['genderhidden']) &&
                strcmp($vars['genderhidden'], self::BW_TRUE) == 0)) {
            $vars['genderhidden'] = self::BW_FALSE;
        }

        if (isset($vars['geonameid'])) {
            $vars['IdCity'] = $vars['geonameid'];
        }

        $vars['email'] = strtolower($vars['email']);

        $escapeList = array('username', 'email', 'gender',
                            'feedback', 'housenumber', 'street','FirstName','SecondName','LastName', 'zip');
        foreach($escapeList as $formfield) {
            if(!empty($vars[$formfield])) {  // e.g. feedback...
                $vars[$formfield] = $this->dao->escape($vars[$formfield]);
            }
        }
        if (isset($vars['mothertongue'])) {
            $language = $this->createEntity('Language', $vars['mothertongue']);
            $vars['mothertonguename'] = $language->Name;
        }
    }

    private function checkStepOne(&$vars)
    {
        $errors = array();

        // username
        try {
            if (!isset($vars['username']) ||
                !preg_match(self::HANDLE_PREGEXP, $vars['username']) ||
                strpos($vars['username'], 'xn--') !== false) {
                $errors[] = 'SignupErrorWrongUsername';
            } elseif ($this->UsernameInUse($vars['username'])) {
                $errors[] = 'SignupErrorUsernameAlreadyTaken';
            }
        } catch (PException $e) {
            $errors[] = 'SignupErrorUsernameAlreadyTaken';
        }

        // email (e-mail duplicates in BW database *not* allowed (as of 1st May 2013, ticket ))
        if (!isset($vars['email']) || !PFunctions::isEmailAddress($vars['email'])) {
            $errors[] = 'SignupErrorInvalidEmail';
        }

        if (!isset($vars['emailcheck']) || strcmp($vars['email'], $vars['emailcheck']) != 0) {
            $errors[] = 'SignupErrorEmailCheck';
        }

        if (isset($vars['email'])) {
            $users = $this->takeCareForNonUniqueEmailAddress($vars['email']);
            if ($users != '') {
                $errors[] = 'SignupErrorEmailAddressAlreadyInUse';
            }
        }

        // password
        if (!isset($vars['password']) || !isset($vars['passwordcheck']) ||
            strlen($vars['password']) < 6 ||
            strcmp($vars['password'], $vars['passwordcheck']) != 0
        ) {
            $errors[] = 'SignupErrorPasswordCheck';
        }

        // accommodation
        if (empty($vars['accommodation']) || ($vars['accommodation']!='anytime' && $vars['accommodation']!='dependonrequest'
                && $vars['accommodation']!='neverask')) {
            $errors[] = 'SignupErrorProvideAccommodation';
        }

        if (!empty($vars['sweet'])) {
            $errors[] = 'SignupErrorSomethingWentWrong';
        }
        return $errors;
    }

    /**
     * @param $vars
     * @return array
     */
    private function checkStepTwo(&$vars)
    {
        $errors = [];
        // firstname, lastname
        if (empty($vars['firstname']) || empty($vars['lastname']))
        {
            $errors[] = 'SignupErrorFullNameRequired';
        }

        if (!isset($vars['mothertongue']) || ($vars['mothertongue'] == "")) {
            $errors[] = 'SignupErrorNoMotherTongue';
        }

        // gender
        if (empty($vars['gender']) || ($vars['gender']!='female' && $vars['gender']!='male'
                && $vars['gender']!='other')) {
            $errors[] = 'SignupErrorProvideGender';
        }

        // birthdate
        if (empty($vars['birthdate'])) {
            $errors[] = 'SignupErrorBirthDate';
        } else {
            $vars['iso_date'] = $vars['birthdate'];
            if ($this->ageValue($vars['iso_date']) < self::YOUNGEST_MEMBER) {
                $errors[] = 'SignupErrorBirthDateToLow';
            }
        }
        return $errors;
    }

    private function checkStepThree(&$vars)
    {
        $errors = [];
        // geonameid
        if (empty($vars['location-geoname-id'])) {
            $errors[] = 'SignupErrorProvideLocation';
            unset($vars['location-geoname-id']);
        }

        // latitude
        if (empty($vars['location-latitude'])) {
            $errors[] = 'SignupErrorProvideLocation';
            unset($vars['location-latitude']);
        }

        // longitude
        if (empty($vars['location-longitude'])) {
            $errors[] = 'SignupErrorProvideLocation';
            unset($vars['location-longitude']);
        }

        return $errors;
    }

    private function checkStepFour(&$vars)
    {
        $errors = [];

        // terms
        if (empty($vars['terms']) || !$vars['terms']) {
            $errors[] = 'SignupMustAcceptTerms';
        }
        if (empty($vars['newsletters']) || !$vars['newsletters']) {
            $errors[] = 'SignupReceiveNewsletters';
        }
        if (empty($vars['local-info']) || !$vars['local-info']) {
            $errors[] = 'SignupReceiveLocalInfo';
        }

        return $errors;
    }

    /**
     * Check form values of registration form,
     * do some cautious corrections
     *
     * @param $vars
     * @param $step
     * @return array
     */
	public function checkRegistrationForm(&$vars, $step)
    {
        $errors = [];
        switch($step)
        {
            case 1:
                $errors = $this->checkStepOne($vars);
                break;
            case 2:
                $errors = $this->checkStepTwo($vars);
                break;
            case 3:
                $errors = $this->checkStepThree($vars);
                break;
            case 4:
                $errors = $this->checkStepFour($vars);
                break;
        }
        return $errors;
    }

    /** @return float (?!) value corresponding date
	 *
	 * FIXME: copied from FunctionsTools.php fage_value;
	 * used in several places in BW website;
	 * where should this been moved to?
	 */
	public function ageValue($dd)
	{
		$iDate = strtotime($dd);
		$age = (time() - $iDate) / (365 * 24 * 60 * 60);
		return ($age);
	}

	/**
	 * (stolen from FunctionsTools->CheckEmail)
	 * @return true , if e-mail address looks valid
	 */
	public function checkEmail($email)
	{
		return filter_var($email, FILTER_VALIDATE_EMAIL);
	}

    /**
     * Resend the confirmation mail in case the user clicked on the link in the
     * login error message
     *
     * @param string $username
     * @return bool|string
     * @throws PException
     */
	public function resendConfirmationMail($username) {
        // fetch ID for member $username
        $vars = array();
        $MembersModel = new MembersModel();
        $member = $MembersModel->getMemberWithUsername($username);
        if ($member) {
            if ($member->Status == 'MailToConfirm') {
                $crypted = new MOD_crypt();
                $vars['firstname'] = $crypted->AdminReadCrypted($member->Firstname);
                $vars['secondname'] = $crypted->AdminReadCrypted($member->Secondname);
                $vars['lastname'] = $crypted->AdminReadCrypted($member->Lastname);
                $vars['email'] = $crypted->AdminReadCrypted($member->Email);
                $userId = APP_User::userId($username);
                if( !$userId) {
                    return 'NoSuchMember';
                } else {
                    $View = new SignupView($this);
                    define('DOMAIN_MESSAGE_ID', 'bewelcome.org');    // TODO: config
                    $View->registerMail($vars, $member->id, $userId);
                }
            } else {
                return 'NoMailToConfirm';
            }
        } else {
            return 'NoSuchMember';
        }
        return true;
	}
}

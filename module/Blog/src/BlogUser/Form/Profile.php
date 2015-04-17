<?php
/**
 * Profile Form
 *
 * @category        Form
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Form;
use NBlog\Form\BaseForm;
use NBlog\View\Helper\Number;
use Zend\Form\Element;
use Zend\Form\Fieldset;
use NBlog\Model\Setting;

class Profile extends BaseForm
{
    /**
     * @var     Number
     */
    private $numberConverter = null;
    public function __construct(array $options)
    {
        parent::__construct('user-profile', 'form-horizontal');
        $this->setUseInputFilterDefaults(false);

        $this->setTranslator($options['translator']);
        $this->setNumberConverter($options['numberConverter']);
        $this->addPersonalInfoFieldset($options);
        $this->addEducationalInfoFieldset($options);
        $this->addProfessionalInfoFieldset($options);
        $this->addMoreProfileInfoFieldset();
        $this->addContactInfoFieldset($options);
        $this->addPrivacyFieldset(!empty($options['socialMedias']));
        $this->addSectionPrivacyFieldset();
        $this->add($this->createSubmitButtonElement());
        $this->add($this->createCancelButtonElement());
        $this->add($this->createFormClickedElement());
    }

    protected function addPersonalInfoFieldset(array $options)
    {
        $credentialFieldset = new Fieldset('profile_info');
        $credentialFieldset->setLabel('Personal Information');
        $credentialFieldset->add($this->createNicknameElement());
        $credentialFieldset->add($this->createFirstNameElement());
        $credentialFieldset->add($this->createMiddleNameElement());
        $credentialFieldset->add($this->createLastNameElement());
        $credentialFieldset->add($this->createGenderField($options['genders']));
        $credentialFieldset->add($this->createDayOfBirthElement());
        $credentialFieldset->add($this->createMonthOfBirthElement());
        $credentialFieldset->add($this->createYearOfBirthElement());
        $this->add($credentialFieldset);
        return $this;
    }

    protected function createNicknameElement()
    {
        $element = new Element('nickname');
        $element->setLabel('Nickname');
        $element->setAttributes(array(
            'type' => 'text',
            'id' => 'nickname',
            'placeholder' => $this->getTranslator()->translate('Nickname'),
            'class' => 'span9'
        ));

        return $element;
    }

    protected function createFirstNameElement()
    {
        $element = new Element('first_name');
        $element->setLabel('Name');
        $element->setAttributes(array(
            'type' => 'text',
            'id' => 'first_name',
            'placeholder' => $this->getTranslator()->translate('First name'),
            'class' => 'span6'
        ));

        return $this->addControlLabelAttribute($this->addClassForKeyboardLayout($element));
    }

    protected function createMiddleNameElement()
    {
        $element = new Element('middle_name');
        $element->setLabel('Middle Name');
        $element->setAttributes(array(
            'type' => 'text',
            'id' => 'middle_name',
            'placeholder' => $this->getTranslator()->translate('Middle name'),
            'class' => 'span6'
        ));

        return $this->addControlLabelAttribute($this->addClassForKeyboardLayout($element));
    }

    protected function createLastNameElement()
    {
        $element = new Element('last_name');
        $element->setLabel('Last Name');
        $element->setAttributes(array(
            'type' => 'text',
            'id' => 'last_name',
            'placeholder' => $this->getTranslator()->translate('Last name'),
            'class' => 'span6'
        ));

        return $this->addControlLabelAttribute($this->addClassForKeyboardLayout($element));
    }

    protected function createGenderField($genders)
    {
        $element = new Element\Select('gender');
        $element->setLabel('Gender');
        $element->setAttributes(array(
            'type' => 'select',
            'id' => 'gender',
            'class' => 'form-control for-edit display-hide'
        ));

        $element->setValueOptions($genders);
        return $element;
    }

    protected function createDayOfBirthElement()
    {
        $element = new Element\Select('day_of_birth');
        $element->setLabel('Date of Birth');
        $range = range(1, 31);
        $days = array('' => 'Day');
        $numberConverter = $this->getNumberConverter();
        foreach ($range AS $day) {
            $days[$day] = $numberConverter->convert($day);
        }
        $element->setValueOptions($days);
        $element->setAttribute('class', 'form-control one-third for-edit display-hide');
        return $element;
    }

    protected function createMonthOfBirthElement()
    {
        $element = new Element\Select('month_of_birth');
        $element->setEmptyOption('Month');
        $element->setValueOptions(array(
            1 => $this->getTranslator()->translate('January'),
            2 => $this->getTranslator()->translate('February'),
            3 => $this->getTranslator()->translate('March'),
            4 => $this->getTranslator()->translate('April'),
            5 => $this->getTranslator()->translate('May'),
            6 => $this->getTranslator()->translate('June'),
            7 => $this->getTranslator()->translate('July'),
            8 => $this->getTranslator()->translate('August'),
            9 => $this->getTranslator()->translate('September'),
            10 => $this->getTranslator()->translate('October'),
            11 => $this->getTranslator()->translate('November'),
            12 => $this->getTranslator()->translate('December')
        ));
        $element->setAttribute('class', 'form-control one-third for-edit display-hide');
        return $element;
    }

    protected function createYearOfBirthElement()
    {
        $range = range(1950, date('Y'));
        $years = array('' => 'Year');
        $numberConverter = $this->getNumberConverter();
        foreach ($range AS $year) {
            $years[$year] = $numberConverter->convert($year);
        }
        $element = new Element\Select('year_of_birth');
        $element->setValueOptions($years);
        $element->setAttribute('class', 'form-control one-third for-edit display-hide');
        return $element;
    }

    protected function addEducationalInfoFieldset(array $options)
    {
        $educationalInfoFieldset = new Fieldset('educational_info');
        $educationalInfoFieldset->setLabel('Educational Info');
        $educationalInfoFieldset->add($this->createAcademicDegreeField($options['degrees']));
        $educationalInfoFieldset->add($this->createEducationalInstituteElement());
        $educationalInfoFieldset->add($this->createEducationalFromElement());
        $educationalInfoFieldset->add($this->createEducationalToElement());
       // $educationalInfoFieldset->add($this->createPassingYearElement());
       // $educationalInfoFieldset->add($this->createIsFinishedElement());
        $this->add($educationalInfoFieldset);
        return $this;
    }

    protected function createAcademicDegreeField($degrees)
    {
        $element = new Element\Select('educational_degree_id');
        $element->setLabel('Educational Degree');
        $element->setAttributes(array(
            'type' => 'select',
            'class' => 'form-control for-edit display-hide'
        ));

        $element->setValueOptions($degrees);
        return $element;
    }

    protected function createEducationalInstituteElement()
    {
        $element = new Element('educational_institute');
        $element->setLabel('Educational Institute');
        $element->setAttributes(array(
            'type' => 'text',
            'class' => 'form-control for-edit display-hide',
            'placeholder' => $this->getTranslator()->translate('Educational Institute')
        ));

        return $element;
    }

    protected function createEducationalFromElement()
    {
        $range = range(1990, date('Y'));
        $years = array('' => 'From');
        $numberConverter = $this->getNumberConverter();
        foreach ($range AS $year) {
            $years[$year] = $numberConverter->convert($year);
        }
        $element = new Element\Select('starting_year');
        $element->setValueOptions($years);
        $element->setAttribute('class', 'form-control one-third for-edit display-hide');
        return $element;
    }

    protected function createEducationalToElement()
    {
        $range = range(1990, date('Y'));
        $years = array('' => 'To');
        $numberConverter = $this->getNumberConverter();
        foreach ($range AS $year) {
            $years[$year] = $numberConverter->convert($year);
        }
        $element = new Element\Select('ending_year');
        $element->setValueOptions($years);
        $element->setAttribute('class', 'form-control one-third for-edit display-hide');
        return $element;
    }

    protected function createPassingYearElement()
    {
        $element = new Element('passing_year');
        $element->setLabel('Passing Year');
        $element->setAttributes(array(
            'type' => 'text',
            'class' => 'form-control for-edit display-hide',
            'placeholder' => $this->getTranslator()->translate('Passing Year')
        ));

        return $element;
    }

    protected function createIsFinishedElement()
    {
        $element = new Element\Checkbox('is_education_finished');
        $element->setLabel('Education Finished');
        $element->setUseHiddenElement(true)
            ->setCheckedValue('1')
            ->setUncheckedValue('0');

        return $element;
    }

    protected function addProfessionalInfoFieldset(array $options)
    {
        $professionalInfoFieldset = new Fieldset('professional_info');
        $professionalInfoFieldset->setLabel('Professional Info');
        $professionalInfoFieldset->add($this->createProfessionField($options['professions']));
        $professionalInfoFieldset->add($this->createWorkplaceElement());
        $professionalInfoFieldset->add($this->createDesignationElement());
        $this->add($professionalInfoFieldset);
        return $this;
    }

    protected function createProfessionField($professions)
    {
        $element = new Element\Select('profession');
        $element->setLabel('Profession');
        $element->setAttributes(array(
            'id' => 'profession',
            'class' => 'form-control for-edit display-hide'
        ));

        $element->setValueOptions($professions);
        return $element;
    }

    protected function createWorkplaceElement()
    {
        $element = new Element('workplace');
        $element->setLabel('Institute');
        $element->setAttributes(array(
            'type' => 'text',
            'class' => 'form-control for-edit display-hide',
            'placeholder' => $this->getTranslator()->translate('Institute Name')
        ));

        return $element;
    }

    protected function createDesignationElement()
    {
        $element = new Element('designation');
        $element->setLabel('Designation');
        $element->setAttributes(array(
            'type' => 'text',
            'class' => 'form-control for-edit display-hide',
            'placeholder' => $this->getTranslator()->translate('Designation Name')
        ));

        return $element;
    }

    protected function addMoreProfileInfoFieldset()
    {
        $credentialFieldset = new Fieldset('more_profile_info');
        $credentialFieldset->setLabel('More Profile Info');
        $credentialFieldset->add($this->createHobbyField());
        $credentialFieldset->add($this->createBiographyField());
        $credentialFieldset->add($this->createProfileTaglineField());
        $this->add($credentialFieldset);
        return $this;
    }

    protected function createHobbyField()
    {
        $element = new Element('hobby');
        $element->setLabel('Hobby');
        $element->setAttributes(array(
            'type' => 'textarea',
            'class' => 'form-control for-edit display-hide',
            'rows'  => 3,
            'cols' => 100,
            'placeholder' => $this->getTranslator()->translate('Your Hobby')
        ));

        return $element;
    }

    protected function createBiographyField()
    {
        $element = new Element('biography');
        $element->setLabel('Biography');
        $element->setAttributes(array(
            'type' => 'textarea',
            'class' => 'form-control for-edit display-hide',
            'rows'  => 3,
            'cols' => 100,
            'placeholder' => $this->getTranslator()->translate('Write about yourself')
        ));

        return $element;
    }

    protected function createProfileTaglineField()
    {
        $element = new Element('profile_tagline');
        $element->setLabel('Profile Tagline');
        $element->setAttributes(array(
            'class' => 'form-control for-edit display-hide',
            'placeholder' => $this->getTranslator()->translate('Profile Tagline')
        ));

        return $element;
    }

    protected function addContactInfoFieldset(array $options)
    {
        $contactInfoFieldset = new Fieldset('contact_info');
        $contactInfoFieldset->setLabel('Contact Info');
        $contactInfoFieldset->add($this->createCountryField($options['countries']));
        $contactInfoFieldset->add($this->createDivisionField($options['divisions']));
        $contactInfoFieldset->add($this->createDistrictField($options['districts']));
        $contactInfoFieldset->add($this->createStationField($options['stations']));
        $contactInfoFieldset->add($this->createZipCodeElement($options['offices']));
        $contactInfoFieldset->add($this->createPresentAddressField());
        $contactInfoFieldset->add($this->createPermanentAddressField());
        $contactInfoFieldset->add($this->createWebsiteElement());
        $contactInfoFieldset->add($this->createEmailElement());
        $contactInfoFieldset->add($this->createMobileNumberField());
        empty($options['socialMedias']) || $this->createSocialMediaLinks($contactInfoFieldset, $options['socialMedias']);
        $this->add($contactInfoFieldset);
        return $this;
    }

    protected function createCountryField($countries)
    {
        $element = new Element\Select('country_id');
        $element->setLabel('Country');
        $element->setAttributes(array(
            'type' => 'select',
            'id' => 'country',
            'class' => 'form-control for-edit display-hide'
        ));

        $element->setEmptyOption('Select');
        $element->setValueOptions($countries);
        return $element;
    }

    protected function createDivisionField($divisions)
    {
        $element = new Element\Select('division_id');
        $element->setLabel('Division');
        $element->setAttributes(array(
            'class' => 'form-control for-edit display-hide',
            'id' => 'division'
        ));

        $options = array('' => 'Select') + $divisions;
        $element->setValueOptions($options);
        return $element;
    }

    protected function createDistrictField($districts)
    {
        $element = new Element\Select('district_id');
        $element->setLabel('District');
        $element->setAttributes(array(
            'class' => 'form-control for-edit display-hide',
            'id' => 'district'
        ));

        $options = array('' => 'Select') + $districts;
        $element->setValueOptions($options);
        return $element;
    }

    protected function createStationField($stations)
    {
        $element = new Element\Select('station_id');
        $element->setLabel('Police Station');
        $element->setAttributes(array(
            'class' => 'form-control for-edit display-hide',
            'id' => 'police-station'
        ));

        $options = array('' => 'Select') + $stations;
        $element->setValueOptions($options);
        return $element;
    }

    protected function createZipCodeElement($offices)
    {
        $element = new Element\Select('zip_code');
        $element->setLabel('Zip Code');
        $element->setAttributes(array(
            'class' => 'form-control for-edit display-hide',
            'id' => 'zip_code'
        ));

        $options = array('' => 'Select') + $offices;
        $element->setValueOptions($options);
        return $element;
    }

    protected function createPresentAddressField()
    {
        $element = new Element('present_address');
        $element->setLabel('Present Address');
        $element->setAttributes(array(
            'type' => 'textarea',
            'class' => 'form-control for-edit display-hide',
            'rows'  => 3,
            'cols' => 100,
            'placeholder' => $this->getTranslator()->translate('Present Address')
        ));

        return $element;
    }

    protected function createPermanentAddressField()
    {
        $element = new Element('permanent_address');
        $element->setLabel('Permanent Address');
        $element->setAttributes(array(
            'type' => 'textarea',
            'class' => 'form-control for-edit display-hide',
            'rows'  => 3,
            'cols' => 100,
            'placeholder' => $this->getTranslator()->translate('Permanent Address')
        ));

        return $element;
    }

    protected function createWebsiteElement()
    {
        $element = new Element('website');
        $element->setLabel('Website');
        $element->setAttributes(array(
            'type' => 'text',
            'id' => 'website',
            'class' => 'form-control for-edit display-hide',
            'placeholder' => 'http://'
        ));

        return $element;
    }

    protected function createEmailElement()
    {
        $element = new Element('email');
        $element->setLabel('Email Address');
        $element->setAttributes(array(
            'type' => 'email',
            'id' => 'email',
            'class' => 'form-control for-edit display-hide',
            'placeholder' => $this->getTranslator()->translate('Your Email Address')
        ));

        return $element;
    }

    protected function createMobileNumberField()
    {
        $element = new Element('mobile_number');
        $element->setLabel('Mobile Number');
        $element->setAttributes(array(
            'class' => 'form-control for-edit display-hide',
            'placeholder' => $this->getTranslator()->translate('Mobile Number')
        ));

        return $element;
    }

    protected function createSocialMediaLinks(Fieldset $fieldset, array $socialMedias)
    {
        foreach($socialMedias AS $media) {
            $fieldset->add($this->createSocialMediaLink($media));
        }
    }

    private function createSocialMediaLink($media)
    {
        $element = new Element($media['name']);
        $element->setLabel($media['label']);
        $element->setAttributes(array(
            'class' => 'form-control for-edit display-hide',
            'placeholder' => $media['placeholder']
        ));

        return $element;
    }

    protected function addPrivacyFieldset($mediaBitToBeAdded = false)
    {
        $credentialFieldset = new Fieldset('privacy');
        $credentialFieldset->setLabel('Privacy');
        $credentialFieldset->add($this->createPrivacyElement(Setting::NICKNAME_PRIVACY));
        $credentialFieldset->add($this->createPrivacyElement(Setting::FULLNAME_PRIVACY));
        $credentialFieldset->add($this->createPrivacyElement(Setting::GENDER_PRIVACY));
        $credentialFieldset->add($this->createPrivacyElement(Setting::DOB_PRIVACY));
        $credentialFieldset->add($this->createPrivacyElement(Setting::PROFILE_PICTURE_PRIVACY));
        $credentialFieldset->add($this->createPrivacyElement(Setting::PROFILE_TAGLINE_PRIVACY));
        $credentialFieldset->add($this->createPrivacyElement(Setting::HOBBY_PRIVACY));
        $credentialFieldset->add($this->createPrivacyElement(Setting::BIOGRAPHY_PRIVACY));
        $credentialFieldset->add($this->createPrivacyElement(Setting::COUNTRY_PRIVACY));
        $credentialFieldset->add($this->createPrivacyElement(Setting::DIVISION_PRIVACY));
        $credentialFieldset->add($this->createPrivacyElement(Setting::DISTRICT_PRIVACY));
        $credentialFieldset->add($this->createPrivacyElement(Setting::POLICE_STATION_PRIVACY));
        $credentialFieldset->add($this->createPrivacyElement(Setting::ZIP_CODE));
        $credentialFieldset->add($this->createPrivacyElement(Setting::PRESENT_ADDRESS));
        $credentialFieldset->add($this->createPrivacyElement(Setting::PERMANENT_ADDRESS));
        $credentialFieldset->add($this->createPrivacyElement(Setting::WEBSITE_PRIVACY));
        $credentialFieldset->add($this->createPrivacyElement(Setting::EMAIL_PRIVACY));
        $credentialFieldset->add($this->createPrivacyElement(Setting::MOBILE_PRIVACY));
        if ($mediaBitToBeAdded) {
            $credentialFieldset->add($this->createPrivacyElement(Setting::FACEBOOK_URL_PRIVACY));
            $credentialFieldset->add($this->createPrivacyElement(Setting::TWITTER_URL_PRIVACY));
            $credentialFieldset->add($this->createPrivacyElement(Setting::LINKED_IN_PRIVACY));
            $credentialFieldset->add($this->createPrivacyElement(Setting::GTALK_PRIVACY));
        }
        $credentialFieldset->add($this->createPrivacyElement(Setting::PROFESSION_PRIVACY));
        $credentialFieldset->add($this->createPrivacyElement(Setting::INSTITUTE_PRIVACY));
        $credentialFieldset->add($this->createPrivacyElement(Setting::DESIGNATION_PRIVACY));
        $credentialFieldset->add($this->createPrivacyElement(Setting::EDUCATION_DEGREE_PRIVACY));
        $credentialFieldset->add($this->createPrivacyElement(Setting::EDUCATION_INSTITUTE_PRIVACY));
        $credentialFieldset->add($this->createPrivacyElement(Setting::PASSING_YEAR_PRIVACY));
        $credentialFieldset->add($this->createPrivacyElement(Setting::EDUCATION_FINISHED_PRIVACY));
        $this->add($credentialFieldset);
        return $this;
    }

    private function createPrivacyElement($fieldName)
    {
        $lowerCase = strtolower($fieldName);
        $element = new Element\Hidden($lowerCase);
        return $element->setAttributes(array(
            'id' => $lowerCase
        ));
    }

    protected function addSectionPrivacyFieldset()
    {
        $credentialFieldset = new Fieldset('section_privacy');
        $credentialFieldset->setLabel('Could be seen');
        $credentialFieldset->add($this->createSectionPrivacyElement(Setting::SECTION_PERSONAL_INFO_PRIVACY));
        $credentialFieldset->add($this->createSectionPrivacyElement(Setting::SECTION_PROFILE_INFO_PRIVACY));
        $credentialFieldset->add($this->createSectionPrivacyElement(Setting::SECTION_CONTACT_INFO_PRIVACY));
        $credentialFieldset->add($this->createSectionPrivacyElement(Setting::SECTION_EDUCATION_INFO_PRIVACY));
        $credentialFieldset->add($this->createSectionPrivacyElement(Setting::SECTION_PROFESSIONAL_INFO_PRIVACY));
        $this->add($credentialFieldset);
        return $this;
    }

    protected function createSectionPrivacyElement($fieldName)
    {
        $lowerCase = strtolower($fieldName);
        $element = new Element\Radio($lowerCase);
        $element->setLabel("$fieldName");
        $element->setAttributes(array(
            'type' => 'radio',
            'class' => 'privacy_radio',
            'id' => $lowerCase
        ));
        $element->setValueOptions(array(
            '1' => $this->getTranslator()->translate('Public'),
            '2' => $this->getTranslator()->translate('Friends'),
            '3' => $this->getTranslator()->translate('Private'),
            '4' => $this->getTranslator()->translate('Custom')
        ));

        return $element;
    }

    protected function createSubmitButtonElement()
    {
        return array(
            'name' => 'submit',
            'attributes' => array(
                'type' => 'button',
                'value' => $this->getTranslator()->translate('Submit'),
                'class' => 'btn btn-primary'
            )
        );
    }

    protected function createCancelButtonElement()
    {
        return array(
            'name' => 'cancel',
            'attributes' => array(
                'type' => 'button',
                'value' => 'Cancel',
                'class' => 'btn primary'
            )
        );
    }

    protected function createFormClickedElement()
    {
        $element = new Element\Hidden('form_submitted');
        $element->setAttribute('id', 'form_to_be_submitted');
        return $element;
    }

    private function setNumberConverter(Number $numberConverter)
    {
        $this->numberConverter = $numberConverter;
    }

    /**
     * @return  \NBlog\View\Helper\Number
     * @throws  \Exception
     */
    private function getNumberConverter()
    {
        if (empty($this->numberConverter)) {
            throw new \Exception($this->getTranslator()->translate('Number converter has not been set.'));
        }

        return $this->numberConverter;
    }
}
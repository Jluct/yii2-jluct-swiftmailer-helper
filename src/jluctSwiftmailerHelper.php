<?php
/**
 * Created by PhpStorm.
 * User: Listopadov
 * Date: 07.10.2016
 * Time: 7:56
 *
 * Настройки - массив emailGroups - группы пользователей
 *
 */

namespace app\components\jluct\jluctSwiftmailerHelper;


use yii\base\Component;
use Yii;


class jluctSwiftmailerHelper extends Component
{
    /**
     * @var array - массив адресов для отправки пользователям
     */
    private $data = [];

    /**
     * @var array - $this->data[setting]
     */
    private $setting = [];

    /**
     * @var array - $this->data[internalTarget]
     */
    private $internalTarget = [];

    /**
     * @var array - $this->data[messages]
     */
    private $messages = [];

    private $emailBase;

    /**
     * jluctSwiftmailerHelper constructor.
     * @param string $emailBase - настройки рассылки.
     * @param array $config - передаётся последним для конструктора предка
     */
    public function __construct( $emailBase, array $config = [])
    {


        $this->setEmailBase($emailBase);

        $this->setMailer(Yii::$app->mailer);

        parent::__construct($config);


    }

    /**
     * @param array $data - данные отправки
     * 'setting' => - настройки по умаолчанию для всех сообщений. Переопределяются в messages.
     * Если для конкретного адреса не указан параметр он будет взят из setting
     * [
     *      'from' => '' - адресс отправителя
     *      'layout' =>'' - макет
     *      'view' =>'' -  представления
     *      'subject' =>'' -  отправитель
     * ],
     * 'messages'=>[
     * - настройки конкретного сообщения.
     * Можно будет передать одномерный массив с адресами, а настройки указать в массиве setting (None)
     *      [
     *          'address'=> '',
     *          'from' => '', - адрес отправителя
     *          'layout' => '' - макет
     *          'view' => ''-  представление
     *          'subject' =>'' -  отправитель
     *          'params' => [] - передаваемые параметры
     *      ],
     *      [...],
     * ],
     * 'internalTarget'=>[    дополнительное оповещение сотрудников
     *      'target' => [], - группа оповещения: required,non-required
     *      'from' => '' - адресс отправителя
     *      'layout' =>'' - макет
     *      'view' =>''-  представление
     *      'subject' =>'' -  отправитель
     *      'params' => [] - параметры
     *  ]
     * @return bool
     */
    public function sendAllEmailMessages(array $data)
    {
        $this->setData($data);

        if ($this->sendEmailMessages()) {

            if ($this->sendInternalEmailMessage())
                return true;
        }

        return false;
    }

    public function sendInternalEmailMessage()
    {

        $items = $this->getInternalTarget('target');
        if (!empty($items))
            foreach ($items as $value) {
                $targetEmail = $this->getEmailBase()['emailGroups'][$value];
                foreach ($targetEmail as $item) {
                    $data = $this->fabricMessages($this->getInternalTarget(), $item);
                    if (!$this->sendEmailMessage($data))
                        return false;
                }
            }

        return true;
    }

    /**
     * @return boolean
     */
    public function sendEmailMessages()
    {

        $messages = $this->getMessages();
        foreach ($messages as $message)
            if (!$this->sendEmailMessage($message))
                return false;

        return true;

    }

    public function sendEmailMessage(array $message)
    {

        if(!$message['address'])
            return false;

        $data = $this->addDataSetting($message);
        $data['address'] = $message['address'];

        $mailer = $this->getMailer();
        $mailer->htmlLayout = '@app/mail/layouts/' . $data['layout'] ;
        if (!$mailer->compose($data['view'], ['params' => $data['params']])
            ->setFrom($data['from'])
            ->setTo($data['address'])
            ->setSubject($data['subject'])
            ->send()
        )
            return false;
        return true;

    }

    private function fabricMessages($internalData, $target = null)
    {
        $out = $this->addDataSetting($internalData);

        $out['address'] = $target;

        return $out;
    }
    
    /**
     * Добавляет данные из массива setting в случае отсутствия данных в самом сообщении
     * @param array $data - данные сообщения
     * @return array|bool - массив сообщения или false в случае отсутствия одного из параметров
     */
    private function addDataSetting(array $data)
    {
        $out = [];

        $out['from'] = isset($data['from']) ? $data['from'] : $this->getSetting('from');
        $out['layout'] = isset($data['layout']) ? $data['layout'] : $this->getSetting('layout');
        $out['view'] = isset($data['view']) ? $data['view'] : $this->getSetting('view');
        $out['subject'] = isset($data['subject']) ? $data['subject'] : $this->getSetting('subject');
        $out['params'] = isset($data['params']) ? $data['params'] : null;

        foreach ($out as $value)
            if(empty($value))
                return false;

        return $out;
    }

    /*****************************
     *****************************
     ***   Геттеры и сеттеры   ***
     *****************************
     ****************************/

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @param array $messages
     */
    public function setMessages($messages)
    {
        $this->messages = $messages;
    }

    /**
     * @param string $key
     * @return array
     */
    public function getInternalTarget($key = null)
    {
        return $key != null ? isset($this->internalTarget[$key]) ? $this->internalTarget[$key] : false : $this->internalTarget;
    }

    /**
     * @param array $internalTarget
     */
    public function setInternalTarget($internalTarget)
    {
        $this->internalTarget = $internalTarget;
    }

    /**
     * @param string $key
     * @return array
     */
    public function getSetting($key = null)
    {
        return $key != null ? isset($this->setting[$key]) ? $this->setting[$key] : false : $this->setting;
    }

    /**
     * @param array $setting
     */
    public function setSetting($setting)
    {
        $this->setting = $setting;
    }

    /**
     * @var - object Yii::$app->mailer
     */
    private $mailer;

    /**
     * @return mixed
     */
    public function getMailer()
    {
        return $this->mailer;
    }

    /**
     * @param object object Yii::$app->mailer
     */
    public function setMailer($mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * @param string $param - ключ, по которому предоставляются данные из $this->data
     * @return array $this->data[$param] || $this->data
     */
    public function getData($param = null)
    {

        return $param != null ? isset($this->data[$param]) ? $this->data[$param] : false : $this->data;
    }

    /**
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;

        $this->setInternalTarget($this->getData('internalTarget'));
        $this->setMessages($this->getData('messages'));
        $this->setSetting($this->getData('setting'));
    }

    /**
     * @return array
     */
    public function getEmailBase()
    {
        return $this->emailBase;
    }

    /**
     * @param array $emailBase
     */
    public function setEmailBase($emailBase)
    {
        $this->emailBase = $emailBase;
    }

}
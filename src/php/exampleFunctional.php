<?php
set_time_limit(10);
require_once 'whatsprot.class.php';
require_once 'contacts.php';

// phone number, deviceIdentity, and name.
$options = getopt("d::", array("debug::"));
$debug = (array_key_exists("debug", $options) || array_key_exists("d", $options)) ? true : false;

$username = "**your phone number**";                       // Telephone number including the country code without '+' or '00'.
$identity = "**unique ID generated by WhatsApp client**";  // Obtained during registration with this API or using MissVenom (https://github.com/shirioko/MissVenom) to sniff from your phone.
$password = "**server generated whatsapp password**";      // A server generated Password you received from WhatsApp. This can NOT be manually created
$nickname = "**your nickname**";                           // This is the username (or nickname) displayed by WhatsApp clients.
$target = "**contact's phone number**";                    // Destination telephone number including the country code without '+' or '00'.

//This function only needed to show how eventmanager works.
function onGetProfilePicture($from, $target, $type, $data)
{
    if ($type == "preview") {
        $filename = "preview_" . $target . ".jpg";
    } else {
        $filename = $target . ".jpg";
    }
    $filename = WhatsProt::PICTURES_FOLDER."/" . $filename;
    $fp = @fopen($filename, "w");
    if ($fp) {
        fwrite($fp, $data);
        fclose($fp);
    }
}

//Create the whatsapp object and setup a connection.
$w = new WhatsProt($username, $identity, $nickname, $debug);
$w->connect();

// Now loginWithPassword function sends Nickname and (Available) Presence
$w->loginWithPassword($password);

//Retrieve large profile picture. Output is in /src/php/pictures/ (you need to bind a function
//to the event onProfilePicture so the script knows what to do.
$w->eventManager()->bind("onGetProfilePicture", "onGetProfilePicture");
$w->sendGetProfilePicture($target, true);

//update your profile picture
$w->sendSetProfilePicture("demo/venom.jpg");

//send picture
$w->sendMessageImage($target, "demo/x3.jpg");

//send video
//$w->sendMessageVideo($target, 'http://techslides.com/demos/sample-videos/small.mp4');

//send Audio
//$w->sendMessageAudio($target, 'http://www.kozco.com/tech/piano2.wav');

//send Location
//$w->sendLocation($target, '4.948568', '52.352957');



// Implemented out queue messages and auto msgid
$w->sendMessage($target, "Sent from WhatsApi at " . time());

/**
 * You can create a ProcessNode class (or whatever name you want) that has a process($node) function
 * and pass it through setNewMessageBind, that way everytime the class receives a text message it will run
 * the process function to it.
 */
$pn = new ProcessNode($w, $target);
$w->setNewMessageBind($pn);

while (1) {
    $w->pollMessages();
    $msgs = $w->getMessages();
    foreach ($msgs as $m) {
        # process inbound messages
        //print($m->NodeString("") . "\n");
    }
}

/**
 * Demo class to show how you can process inbound messages
 */
class ProcessNode
{
    protected $wp = false;
    protected $target = false;

    public function __construct($wp, $target)
    {
        $this->wp = $wp;
        $this->target = $target;
    }

    /**
     * @param ProtocolNode $node
     */
    public function process($node)
    {
        // Example of process function, you have to guess a number (psss it's 5)
        // If you guess it right you get a gift
        $text = $node->getChild('body');
        $text = $text->getData();
        if ($text && ($text == "5" || trim($text) == "5")) {
            $iconfile = "../../tests/Gift.jpgb64";
            $fp = fopen($iconfile, "r");
            $icon = fread($fp, filesize($iconfile));
            fclose($fp);
            $this->wp->sendMessageImage($this->target, "https://mms604.whatsapp.net/d11/26/09/8/5/85a13e7812a5e7ad1f8071319d9d1b43.jpg", "hero.jpg", 84712, $icon);
            $this->wp->sendMessage($this->target, "Congratulations you guessed the right number!");
        } else {
            $this->wp->sendMessage($this->target, "I'm sorry, try again!");
        }
    }

}

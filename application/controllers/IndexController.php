<?php
/*
*@autor Luca Martini
*@category email DB parser
*/
class IndexController extends Zend_Controller_Action {
    protected $_flashMessenger = null;

    /**
     * redirect to fetch action
     * @name indexAction
     */
    public function indexAction() {
        $this->_redirect('/index/fetch');
    }

    /**
     * fetch the emails
     * @name fetchAction
     */
    public function fetchAction() {
        // load xml configuration
        $outputconfig = new Zend_Config_Xml ('./config/config.xml', 'production');

        // instance a message repository
        $messRepo = new MessageRepository ();

        // instance a Zend Mail Storage pop3
        $mail = Zend_Registry::get ("mail-server");

        // count number of new messages on the server
        $messageNo = $mail->countMessages();

        // create an autoreply mail
        $message = new Zend_Mail ();

        // iterate incoming mails
        foreach ($mail as $messageNum => $message) {


            // extract email subject
            try {
                $subject = $message->subject;
            } catch (Zend_Mail_Exception $e) {
                $subject = $e->getMessage();
            }
            if (empty ($subject)) {
                $subject = "No-Subject";
            }

            // clean fs names windows
            $subject = checkWinFileSystem($subject);

            // set the flags default value
            $spam = false;
            $auth = false;

            // init fields
            $from = "";
            $cc = "";

            // get the email headers
            $emails = extract_emails_from(htmlspecialchars($message->getHeader("from", "string")));

            // check anti spam
            // Effettua il controllo anti-spam escludendo mittenti sulla base
            // dei record inseriti
            // nella tabella del database MySql tts.SpamFilter
            foreach ($emails as $email) {
                $mess = $messRepo->getSpamEmail($email);
                if (count($mess) > 0) {
                    $spam = true;
                }
            }

            // Imposta il campo from
            $from = $email;

            // Effettua il controllo richieste autorizzate basandosi sulla base
            // dei record inseriti
            // nella tabella del database MySql tts.MitarBeiter
            foreach ($emails as $email) {
                $mess = neeRepositoryessaggio ();
                $mess = $mess->getAuthEmail($email);
                if (count($mess) > 0) {
                    $auth = true;
                }
            }

            // Carica degli indirizzi a cui inviare comunicazione sul ticket
            try {
                $emails = extract_emails_from(htmlspecialchars($message->getHeader("cc", "string")));
                foreach ($emails as $email) {
                    $cc = $cc . $email . "; ";
                }
            } catch (Zend_Mail_Exception $e) {
                $emails = ";";
            }

            // Controlla l'autorizzazione della richiesta
            if (!$auth) {
                $auth = 0;
            } else {
                $auth = 1;
            }

            // Se la mail non è SPAM avvia il Parsing
            if (!$spam) {

                // Fissa il nome della cartella per ottimizzare la catalogazione
                $subdirectory = date(Ymd) . "/" . $from . "/" . date(YmdHis) . "_" . $subject . "/";

                // Effettua la distinzione tra messaggi multipart e normali
                if ($message->isMultipart()) {

                    // Inizio scansione messaggio multipart
                    $filearray = array();
                    $htmlarray = array();

                    // Conta le parti del messaggio
                    $partNumber = $message->countParts();

                    // Cicla le parti del messaggio
                    foreach (new RecursiveIteratorIterator ($message) as $partNum => $part) {

                        // Rilevazione del tipo di contenuto
                        $contentType = $part->contentType;

                        // Prova a vedere se la parte in realtà è un file
                        try {
                            $nomefile = $part->getHeaderField('content-disposition', 'filename');
                        } catch (Zend_Mail_Exception $e) {
                            $nomefile = $e->getMessage();
                        }

                        if (($nomefile != "no Header with Name content-disposition found") && !(empty ($nomefile))) {
                            // Si tratta di un file da scrivere sul file system
                            // web

                            // determina il tipo di codifica per l'allegato
                            try {
                                $encoding = $part->getHeaderField('content-transfer-encoding');
                            } catch (Zend_Mail_Exception $e) {
                                $encoding = $e->getMessage();
                            }

                            // Decodifica del file
                            if (($encoding == "7bit") || ($encoding == "8bit") || ($encoding == "binary")) {
                                $contentToSave = $part->getContent();
                            } elseif ($encoding == "quoted-printable") {
                                $contentToSave = quoted_printable_decode($part->getContent());
                            } elseif ($encoding == "base64") {
                                $contentToSave = base64_decode($part->getContent());
                            } else {
                                $contentToSave = $part->getContent();
                            }

                            $estensione = strrchr($nomefile, ".");

                            // Rimuove caratteri antipatici e mette in un array
                            // il nome del file
                            $filearray [$partNum] = array(
                                "name" => $subdirectory . checkWinFileSystem($nomefile),
                                "encoding" => $encoding
                            );

                            // Costruisce il nome del file completo di path
                            $nomefile = $outputconfig->outputdirectory->path . $subdirectory . checkWinFileSystem($nomefile);

                            // Scrittura file
                            mkdir($outputconfig->outputdirectory->path . $subdirectory, 0777, true);
                            $handle = fopen($nomefile, 'w');
                            $a = fwrite($handle, $contentToSave);
                            fclose($handle);

                        } elseif (strtok($contentType, ';') == "text/html") {

                            // Si tratta di una parte HTML da scrivere sul file
                            // system web

                            // determina il tipo di codifica per la parte html
                            try {
                                $encoding = $part->getHeaderField('content-transfer-encoding');
                            } catch (Zend_Mail_Exception $e) {
                                $encoding = $e->getMessage();
                            }

                            // Decodifica della parte html
                            if (($encoding == "7bit") || ($encoding == "8bit") || ($encoding == "binary")) {
                                $contentToSave = $part->getContent();
                            } elseif ($encoding == "quoted-printable") {
                                $contentToSave = quoted_printable_decode($part->getContent());
                            } elseif ($encoding == "base64") {
                                $encoded = checkBase64Encoded($part->getContent());
                                if ($encoded) {
                                    $contentToSave = base64_decode($part->getContent());
                                } else {
                                    // Costruisce il nome del file
                                    // Vecchia riga ---> $nomefile =
                                    // $oggetto."_".date("YmdHis").".eml";
                                    $nomefile = $subject . ".eml";

                                    // Genera il messaggio
                                    $messaggio = "Si è verificato un errore nel messaggio (base64 non valida), consultare l'allegato per visualizzare l' oggetto.<br/><a href=\"" . $outputconfig->httpdownload->url . $subdirectory . $nomefile . "\" target=\"new\">" . $nomefile . "</a>";

                                    // Costruisce il nome del file completo di
                                    // path
                                    $nomefile = $outputconfig->outputdirectory->path . $subdirectory . $nomefile;

                                    // Scrittura file eml
                                    $buffer = "";
                                    // Include headers
                                    foreach ($message->getHeaders() as $name => $value) {
                                        if (is_string($value)) {
                                            if (isset ($name) && isset ($value)) {
                                                $buffer = $buffer . "$name: $value" . "\n";
                                                continue;
                                            }
                                        }
                                        foreach ($value as $entry) {
                                            if (isset ($name) && isset ($entry)) {
                                                $buffer = $buffer . "$name: $entry" . "\n";
                                            }
                                        }
                                    }
                                    // Scrive contenuto
                                    mkdir($outputconfig->outputdirectory->path . $subdirectory, 0777, true);
                                    $handle = fopen($nomefile, 'w');
                                    $a = fwrite($handle, $buffer . "\n" . $message->getContent());
                                    fclose($handle);
                                }
                            } else {
                                // da controllare
                                $contentToSave = $part->getContent();
                            }

                            // Imposta l'estensione html
                            $estensione = ".html";

                            // Costruisce il nome del file
                            // Vecchia riga ---> $nomefile =
                            // $oggetto."_".date("YmdHis").$estensione;
                            $nomefile = $subject . $estensione;

                            // Memorizza il nome del file in un array
                            $htmlarray [$partNum] = array(
                                "name" => $subdirectory . $nomefile,
                                "encoding" => $encoding
                            );

                            // Costruisce il nome del file completo di path
                            $nomefile = $outputconfig->outputdirectory->path . $subdirectory . $nomefile;

                            // Scrittura file html
                            mkdir($outputconfig->outputdirectory->path . $subdirectory, 0777, true);
                            $handle = fopen($nomefile, 'w');
                            $a = fwrite($handle, $contentToSave);
                            fclose($handle);
                        } else {

                            // Tutti gli altri casi

                            // determina il tipo di codifica per la parte
                            try {
                                $encoding = $part->getHeaderField('content-transfer-encoding');
                            } catch (Zend_Mail_Exception $e) {
                                $encoding = $e->getMessage();
                            }

                            // Decodifica della parte
                            if (($encoding == "7bit") || ($encoding == "8bit") || ($encoding == "binary")) {
                                $messaggio = $part->getContent();
                            } elseif ($encoding == "quoted-printable") {
                                $messaggio = quoted_printable_decode($part->getContent());
                            } elseif ($encoding == "base64") {
                                $encoded = checkBase64Encoded($part->getContent());
                                if ($encoded) {
                                    $messaggio = base64_decode($part->getContent());
                                } else {
                                    // Costruisce il nome del file
                                    $nomefile = $subject . "_" . date("YmdHis") . ".eml";

                                    // Genera il messaggio
                                    $messaggio = "Si è verificato un errore nel messaggio (base64 non valida), consultare l'allegato per visualizzare l' oggetto.<br/><a href=\"" . $outputconfig->httpdownload->url . $subdirectory . $nomefile . "\" target=\"new\">" . $nomefile . "</a>";

                                    // Costruisce il nome del file completo di
                                    // path
                                    $nomefile = $outputconfig->outputdirectory->path . $subdirectory . $nomefile;

                                    // Scrittura file eml
                                    $buffer = "";
                                    // Include headers
                                    foreach ($message->getHeaders() as $name => $value) {
                                        if (is_string($value)) {
                                            if (isset ($name) && isset ($value)) {
                                                $buffer = $buffer . "$name: $value" . "\n";
                                                continue;
                                            }
                                        }
                                        foreach ($value as $entry) {
                                            if (isset ($name) && isset ($entry)) {
                                                $buffer = $buffer . "$name: $entry" . "\n";
                                            }
                                        }
                                    }
                                    // Scrive contenuto
                                    mkdir($outputconfig->outputdirectory->path . $subdirectory, 0777, true);
                                    $handle = fopen($nomefile, 'w');
                                    $a = fwrite($handle, $buffer . "\n" . $message->getContent());
                                    fclose($handle);
                                }
                            } else {
                                $messaggio = $part->getContent();
                            }
                        }
                        $nomefile = "";
                    }

                    // Aggiunge i file html come collegamenti
                    if (!empty ($htmlarray)) {
                        $messaggio = $messaggio . "<p><b>Messaggio HTML<b><ul>";
                        foreach ($htmlarray as $value) {
                            $realname = strrpos($value ["name"], "/") + 1;
                            $realname = substr($value ["name"], $realname);
                            $messaggio = $messaggio . "<li><a href=\"" . $outputconfig->httpdownload->url . $value ["name"] . "\" target=\"_new\"><img src=\"images/downloads.png\" style=\"vertical-align: middle;\"></img>" . "</a> " . "<a href=\"" . $outputconfig->httpdownload->url . $value ["name"] . "\" target=\"_new\">" . $realname . "</a></li>";
                        }
                        $messaggio = $messaggio . "</ul></p>";
                    }

                    // Aggiunge gli attachment come collegamenti
                    if (!empty ($filearray)) {
                        $messaggio = $messaggio . "<p><b>File allegati</b><ul>";
                        foreach ($filearray as $value) {
                            $realname = strrpos($value ["name"], "/") + 1;
                            $realname = substr($value ["name"], $realname);
                            $messaggio = $messaggio . "<li><a href=\"" . $outputconfig->httpdownload->url . $value ["name"] . "\" target=\"_new\"><img src=\"images/downloads.png\" style=\"vertical-align: middle;\"></img>" . "</a> " . "<a href=\"" . $outputconfig->httpdownload->url . $value ["name"] . "\" target=\"_new\">" . $realname . "</a></li>";
                        }
                        $messaggio = $messaggio . "</ul></p>";
                    }

                    // Fine Messaggio Multipart

                } else {
                    // determina il tipo di codifica per la parte
                    try {
                        $encoding = $message->getHeaderField('content-transfer-encoding');
                    } catch (Zend_Mail_Exception $e) {
                        $encoding = $e->getMessage();
                    }

                    // Decodifica della parte
                    if (($encoding == "7bit") || ($encoding == "8bit") || ($encoding == "binary")) {
                        $messaggio = $message->getContent();
                    } elseif ($encoding == "quoted-printable") {
                        $messaggio = quoted_printable_decode($message->getContent());
                    } elseif ($encoding == "base64") {
                        $encoded = checkBase64Encoded($message->getContent());
                        if ($encoded) {
                            $messaggio = base64_decode($message->getContent());
                        } else {
                            // Costruisce il nome del file
                            $nomefile = $subject . "_" . date("YmdHis") . ".eml";

                            // Genera il messaggio
                            $messaggio = "Si è verificato un errore nel messaggio (base64 non valida), consultare l'allegato per visualizzare l' oggetto.<br/><a href=\"" . $outputconfig->httpdownload->url . $subdirectory . $nomefile . "\" target=\"new\">" . $nomefile . "</a>";

                            // Costruisce il nome del file completo di path
                            $nomefile = $outputconfig->outputdirectory->path . $subdirectory . $nomefile;

                            // Scrittura file eml
                            $buffer = "";
                            // Include headers
                            foreach ($message->getHeaders() as $name => $value) {
                                if (is_string($value)) {
                                    if (isset ($name) && isset ($value)) {
                                        $buffer = $buffer . "$name: $value" . "\n";
                                        continue;
                                    }
                                }
                                foreach ($value as $entry) {
                                    if (isset ($name) && isset ($entry)) {
                                        $buffer = $buffer . "$name: $entry" . "\n";
                                    }
                                }
                            }
                            // Scrive contenuto
                            mkdir($outputconfig->outputdirectory->path . $subdirectory, 0777, true);
                            $handle = fopen($nomefile, 'w');
                            $a = fwrite($handle, $buffer . "\n" . $message->getContent());
                            fclose($handle);
                        }
                    } else {
                        $messaggio = $message->getContent();
                    }
                }

                // Generazione Password

                $pool = "qwertzupasdfghkyxcvbnm";
                $pool .= "23456789";
                $pool .= "WERTZUPLKJHGFDSAYXCVBNM";

                srand(( double )microtime() * 1000000);

                $password = "";
                for ($index = 0; $index < 5; $index++) {
                    $password .= substr($pool, (rand() % (strlen($pool))), 1);
                }

                $tEinDat = date(YmdHis);
                $tSollEndDat = date(Ymd);

                // Inserimento del messaggio nel DB
                $messRepo->insertMail($password, 5, "new", $subject, addslashes($messaggio), 0, $from, "assign", date("Y-m-d H:i:s"), $tEinDat, $tSollEndDat, null, 0, $cc, $auth);
                $mail->removeMessage($messageNum);
            } else {
                $mail->removeMessage($messageNum);
            }
            // empty buffers array
            unset ($filearray);
            unset ($htmlarray);
        }
    }

    /**
     * redirect to home
     */
    public function norouteAction() {
        $this->_redirect('/index');
    }

    /** redirect to home
     * @param string $method
     * @param array $args
     */
    public function __call($method, $args) {
        $this->_redirect('/index');
    }
}

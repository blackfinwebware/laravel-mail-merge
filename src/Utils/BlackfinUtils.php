<?php
    namespace BlackfinWebware\LaravelMailMerge\Utils;

    use BlackfinWebware\LaravelMailMerge\Mail\BasicMessage;
    use BlackfinWebware\LaravelMailMerge\Mail\BasicQueueableMessage;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Support\Facades\Mail;

    class BlackfinUtils {

        /**
         * Try to eliminate possible glitches in email 'viewability' by reducing smart, multibyte
         * punctuation to its plain ascii equivalents.
         *
         * @param $str
         * @return array|string|string[]
         */
        public static function replaceSmartQuotes($str)
        {
            //UTF-8
            $text = str_replace(
                array("\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x91", "\xe2\x80\x93", "\xe2\x80\x94", "\xe2\x80\xa6"),
                array("'", "'", '"', '"', '-', '-', '--', '...'),
                $str);
            //Windows-1252
            $text = str_replace(
                array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)),
                array("'", "'", '"', '"', '-', '--', '...'),
                $text);
            return $text;
        }

        /**
         * Reduce the space-separated input string to snake case, where each word is lowercase and joined with
         * underscores.
         *
         * @param string $string
         * @return array|string|string[]
         */
        public static function snakeCase(string $string) {
            return str_replace(" ", "_", strtolower($string));
        }

        /**
         * Convert a string to CamelCase, splitting words not only on underscores, but also commas, hyphens or spaces.
         * This is a bit application specific.
         *
         * @param $string
         * @param bool $capitalize_first_word
         * @return string
         */
        public static function camelCase($string, $capitalize_first_word = true){
            //$parts = explode('_', $string);
            $parts = preg_split('/[_,\- ]+/', $string, -1, PREG_SPLIT_NO_EMPTY);
            $parts = $parts ? array_map('ucfirst', $parts) : array($string);
            $parts[0] = $capitalize_first_word ? ucfirst($parts[0]) : lcfirst($parts[0]);
            return implode('', $parts);
        }

        /**
         * Convert a comma, space or return separated list of email addresseses to an array.
         *
         * @param $emailAddresses
         * @return array|false|mixed|string[]
         */
        public static function emailStringToArray($emailAddresses)
        {
            if(strstr($emailAddresses, ',') || strstr($emailAddresses, PHP_EOL)){
                return preg_split("/[\s*\r\n,]+/", $emailAddresses, -1, PREG_SPLIT_NO_EMPTY);
            }
            return $emailAddresses;
        }

        /**
         * Setup and send email using system infrastructure. If sandboxing, and either not in production or debug on,
         * send all emails to the admin email. Reduce multibyte punctuation to plain ascii equivalents to reduce
         * potential legibility problems. If from address isn't set, use system from address set in .env.
         *
         * @param array $customHeaders
         * @param null $attachment
         * @return string
         * @throws \Exception
         */
        public static function sendMail($email,
                                        $customHeaders = [],
                                        $attachment = null) {
            $hostname = gethostname();
            if(!config('mailmerge.sandbox_email') && 'production' == config('app.env')){
                $data['to'] = self::emailStringToArray($email->to);
                $data['body'] = self::replaceSmartQuotes($email->message);
                $data['cc'] = self::emailStringToArray($email->cc);
                if(config('mailmerge.debug')){
                    //get a copy of outbound email if debugging
                    if(!$email->bcc){
                        $data['bcc'] = config('mailmerge.primary_admin_email');
                    }
                    elseif(is_array($email->bcc)){
                        $data['bcc'] = array_merge($email->bcc, [config('mailmerge.primary_admin_email')]);
                    }
                    else{
                        $data['bcc'] = array_merge(self::emailStringToArray($email->bcc), [config('mailmerge.primary_admin_email')]);
                    }
                }
                else{
                    $data['bcc'] = self::emailStringToArray($email->bcc);
                }
                $data['from'] = $email->from;
            }
            else{/* sandbox OR not prod */
                $data['to'] = config('mailmerge.primary_admin_email');
                $data['body'] = "Not in production, so sending email to admin, would have sent it to $email->to.\n\n" . self::replaceSmartQuotes($email->message);
                $data['cc'] = '';
                $data['bcc'] = '';
            }
            if (!$email->subject){
                $data['subject'] = config('app.name') . ' on ' . $hostname . ' on ' . strftime("%b %e %Y", strtotime("today"));
            }
            else{
                $data['subject'] = self::replaceSmartQuotes($email->subject);
            }
            $data['attachment'] = $attachment;
            $data['customHeaders'] = $customHeaders;

            if(empty($data['from'])){
                $data['from'] = env('MAIL_FROM_ADDRESS');
            }

            if($email->replyto){
                $data['replyto'] = $email->replyto;
            }

            try{
                if(config('mailmerge.use_queues')){
                    Mail::to($data['to'])->queue(new BasicQueueableMessage($data));
                }
                else{
                    Mail::to($data['to'])->send(new BasicMessage($data));
                }
            } catch(\Exception $e){
                Log::error(__METHOD__ . ' error encountered ' . $e->getMessage() . ' when trying to send email ' . $e->getTraceAsString());
                throw new \Exception($e->getMessage());
            }
            if (count($failures = Mail::failures()) < 1){
                $message = 0;//"Mail sent successfully.";
                if(config('mailmerge.debug')){
                    Log::debug(__METHOD__ . ' mail sent to : ' . $data['to']);
                }
            }
            else{
                $message = "Problems sending mail to " . implode(", ", $failures);
                Log::error(__METHOD__ . ' error encountered when trying to send email: ' . $message);
            }
            return $message;
        }

        public static function stringBeginsWith($haystack, $needle)
        {
            $length = strlen($needle);
            return (substr($haystack, 0, $length) === $needle);
        }

        public static function stringEndsWith($haystack, $needle)
        {
            $length = strlen($needle);
            if ($length == 0) {
                return true;
            }

            return (substr($haystack, -$length) === $needle);
        }

        /**
         * Case insensitive search for a substring in either a single string or in an array of strings.
         *
         * @param unknown_type $search_subject
         * @param unknown_type $substring
         * @return unknown
         */
        public static function stringContains($haystack, String $needle) {
            if(is_string($haystack)){
                if(mb_stripos($haystack, $needle) !== FALSE){
                    return true;
                }
            }
            elseif(is_array($haystack)){
                foreach($haystack as $string) {
                    if(mb_stripos($string, $needle) !== FALSE){
                        return true;
                    }
                }
            }
            return false;
        }

        /**
         * Extract a classname from a fully namespaced classname.
         *
         * @param String $classname
         * @return false|int|string
         */
        public static function getClassName(String $classname)
        {
            if ($pos = strrpos($classname, '\\')){
                return substr($classname, $pos + 1);
            }
            return $pos;
        }


        /**
         * Convert datetime string to locale aware format. If app.locale set to en_US will return appropriate format
         * for US, otherwise international standard. If short is false will also return seconds and timezone.
         *
         * @param $datetime
         * @param bool $short
         * @return string
         */
        public static function localeDatetime(String $datetime, $short = true) {
            if($datetime){
                if (strcasecmp(config('app.locale'), 'en_US') !== 0){
                    if($short) {
                        $format = 'Y-m-d H:i';
                    }
                    else{
                        $format = 'Y-m-d H:i:s';
                    }
                }
                else{
                    if($short){
                        $format = 'n/j/y g:i a';
                    }
                    else{
                        $format = 'n/j/Y g:i:s a';
                    }
                }

                try{
                    $dt = \Carbon\Carbon::parse($datetime);
                    return $dt->format($format);
                } catch(\Exception $e){
                    return $datetime;
                }
            }
            return '';
        }

        /**
         * Convert date string to locale aware format. If app.locale set to en_US will return appropriate format
         * for US, otherwise international standard.
         *
         * @param String $datetime
         * @return string
         */
        public static function localeDate(String $datetime) {
            if($datetime){
                if (strcasecmp(config('app.locale'), 'en_US') !== 0){
                    $format = 'Y-m-d';
                }
                else{
                    $format = 'n/j/Y';
                }

                try{
                    $dt = \Carbon\Carbon::parse($datetime);
                    return $dt->format($format);
                } catch(\Exception $e){
                    return $datetime;
                }
            }
            return '';
        }
    }

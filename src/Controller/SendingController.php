<?php

namespace App\Controller;

use App\DTO\ContactDTO;
use App\DTO\SendingDTO;
use App\Entity\Audit;
use App\Entity\Contact;
use App\Form\SendingType;
use AmorebietakoUdala\SMSServiceBundle\Services\SmsServiceApi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

/**
 * @Route("/{_locale}")
 */
class SendingController extends AbstractController
{
    /**
     * @Route("/sending/send", name="sending_send")
     */
    public function sendingSendAction(Request $request, SmsServiceApi $smsapi, LoggerInterface $logger)
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $sendingDTO = new SendingDTO();
        $form = $this->createForm(SendingType::class, $sendingDTO);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /* @var $data SendingDTO */
            $data = $form->getData();
            $selected = json_decode($data->getSelected());
            $idsAndTelephones = $this->__extractIdsAndTelephones($selected);
            $telephones = $idsAndTelephones['telephones'];
            if (!empty($data->getTelephone())) {
                $telephones[] = $data->getTelephone();
            }

            if (0 === count($telephones)) {
                $this->addFlash('error', 'No receivers found');

                return $this->render('sending/list.html.twig', [
                    'form' => $form->createView(),
                    'contacts' => [],
                ]);
            }

            if ($this->__checkEmptyTelephones($telephones)) {
                $this->addFlash('error', 'There are empty telephones');

                return $this->render('sending/list.html.twig', [
                    'form' => $form->createView(),
                    'contacts' => [],
                ]);
            }
            try {
                //                $credit = 1000;
                $credit = $smsapi->getCredit();
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error has ocurred: ' . $e->getMessage());

                return $this->render('sending/list.html.twig', [
                    'form' => $form->createView(),
                    'contacts' => [],
                    'credits' => $credit,
                ]);
            }
            if ($credit < count($telephones)) {
                $this->addFlash('error', 'Not enough credit. Needed credits %credits_needed% remaining %credits_remaining%');

                return $this->render('sending/list.html.twig', [
                    'form' => $form->createView(),
                    'contacts' => [],
                    'credits_needed' => count($telephones),
                    'credits_remaining' => $credit,
                ]);
            }

            // Del conjunto caracteres válido están excluidos 4: la é, É, ñ y Ñ aunque están en el juego de caracteres válido.
            if (!$this->__checkGSM7ValidCharacters($data->getMessage())) {
                $this->addFlash('error', 'Message has invalid characters');

                return $this->render('sending/list.html.twig', [
                    'form' => $form->createView(),
                    'contacts' => [],
                    'credits' => $credit,
                ]);
            }

            $audit = Audit::createAudit($telephones, '', '', '', $user, $data->getTelephone());
            try {
                $response = $smsapi->sendMessage($telephones, $data->getMessage(), $data->getDate());
                if (null !== $response) {
                    $audit->setMessage($response['message']);
                    $audit->setResponseCode($response['responseCode']);
                    $audit->setResponse(json_encode($response));
                    $logger->info('API Response: ' . json_encode($response));
                    $this->addFlash('success', '%messages_sent% messages sended successfully');
                } else {
                    $this->addFlash('warning', 'The API has not responded');
                    $logger->info('API Response: The API has not responded');
                }
                $em->persist($audit);
                $em->flush();
                $form = $this->createForm(SendingType::class, new SendingDTO(), []);

                return $this->render('sending/list.html.twig', [
                    'form' => $form->createView(),
                    'contacts' => [],
                    'messages_sent' => count($telephones),
                    'credits' => $smsapi->getCredit(),
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'There was an error processing the request: %error_message%');
                $logger->error('There was an error processing the request: ' . $e->getMessage());
                $em->persist($audit);
                $em->flush();

                return $this->render('sending/list.html.twig', [
                    'form' => $form->createView(),
                    'contacts' => [],
                    'error_message' => $e->getMessage(),
                    'credits' => $smsapi->getCredit(),
                ]);
            }
        }

        return $this->redirectToRoute('sending_search');
    }

    /**
     * @Route("/sending", name="sending_search")
     */
    public function sendSearchAction(Request $request, SmsServiceApi $smsapi)
    {
        $em = $this->getDoctrine()->getManager();
        $form = $this->createForm(SendingType::class, new SendingDTO());

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /* @var $data ContactDTO */
            $data = $form->getData();
            if (count($data->getLabels()) > 0) {
                $contacts = $em->getRepository(Contact::class)->findByLabels($data->getLabels());
            } else {
                $contacts = $em->getRepository(Contact::class)->findAll();
            }

            return $this->render('sending/list.html.twig', [
                'form' => $form->createView(),
                'contacts' => $contacts,
                'credits' => $smsapi->getCredit(),
            ]);
        }

        return $this->render('sending/list.html.twig', [
            'form' => $form->createView(),
            'credits' => $smsapi->getCredit(),
        ]);
    }

    private function __extractIdsAndTelephones($selected)
    {
        $result = [];
        $telephones = [];
        $ids = [];
        foreach ($selected as $jsonContact) {
            $contactDTO = new ContactDTO();
            $contactDTO->extractFromJson($jsonContact);
            $telephones[] = $contactDTO->getTelephone();
            $ids[] = $contactDTO->getId();
        }
        $result['ids'] = $ids;
        $result['telephones'] = $telephones;

        return $result;
    }

    private function __checkGSM7ValidCharacters($message)
    {
        // second column of http://unicode.org/Public/MAPPINGS/ETSI/GSM0338.TXT
        // $gsm338_codepoints = [0x0040, 0x0000, 0x00A3, 0x0024, 0x00A5, 0x00E8, 0x00E9, 0x00F9, 0x00EC, 0x00F2, 0x00E7, 0x00C7, 0x000A, 0x00D8, 0x00F8, 0x000D, 0x00C5, 0x00E5, 0x0394, 0x005F, 0x03A6, 0x0393, 0x039B, 0x03A9, 0x03A0, 0x03A8, 0x03A3, 0x0398, 0x039E, 0x00A0, 0x000C, 0x005E, 0x007B, 0x007D, 0x005C, 0x005B, 0x007E, 0x005D, 0x007C, 0x20AC, 0x00C6, 0x00E6, 0x00DF, 0x00C9, 0x0020, 0x0021, 0x0022, 0x0023, 0x00A4, 0x0025, 0x0026, 0x0027, 0x0028, 0x0029, 0x002A, 0x002B, 0x002C, 0x002D, 0x002E, 0x002F, 0x0030, 0x0031, 0x0032, 0x0033, 0x0034, 0x0035, 0x0036, 0x0037, 0x0038, 0x0039, 0x003A, 0x003B, 0x003C, 0x003D, 0x003E, 0x003F, 0x00A1, 0x0041, 0x0391, 0x0042, 0x0392, 0x0043, 0x0044, 0x0045, 0x0395, 0x0046, 0x0047, 0x0048, 0x0397, 0x0049, 0x0399, 0x004A, 0x004B, 0x039A, 0x004C, 0x004D, 0x039C, 0x004E, 0x039D, 0x004F, 0x039F, 0x0050, 0x03A1, 0x0051, 0x0052, 0x0053, 0x0054, 0x03A4, 0x0055, 0x0056, 0x0057, 0x0058, 0x03A7, 0x0059, 0x03A5, 0x005A, 0x0396, 0x00C4, 0x00D6, 0x00D1, 0x00DC, 0x00A7, 0x00BF, 0x0061, 0x0062, 0x0063, 0x0064, 0x0065, 0x0066, 0x0067, 0x0068, 0x0069, 0x006A, 0x006B, 0x006C, 0x006D, 0x006E, 0x006F, 0x0070, 0x0071, 0x0072, 0x0073, 0x0074, 0x0075, 0x0076, 0x0077, 0x0078, 0x0079, 0x007A, 0x00E4, 0x00F6, 0x00F1, 0x00FC, 0x00E0];
        
        // Debido a los problemas con Acumbamail con la é, É, ñ, y Ñ que los cobraban doble, quitamos eson caracteres del juego de caracteres permitido
        $gsm338_codepoints = [0x0040, 0x0000, 0x00A3, 0x0024, 0x00A5, 0x00E8, 0x00F9, 0x00EC, 0x00F2, 0x00E7, 0x00C7, 0x000A, 0x00D8, 0x00F8, 0x000D, 0x00C5, 0x00E5, 0x0394, 0x005F, 0x03A6, 0x0393, 0x039B, 0x03A9, 0x03A0, 0x03A8, 0x03A3, 0x0398, 0x039E, 0x00A0, 0x000C, 0x005E, 0x007B, 0x007D, 0x005C, 0x005B, 0x007E, 0x005D, 0x007C, 0x20AC, 0x00C6, 0x00E6, 0x00DF, 0x0020, 0x0021, 0x0022, 0x0023, 0x00A4, 0x0025, 0x0026, 0x0027, 0x0028, 0x0029, 0x002A, 0x002B, 0x002C, 0x002D, 0x002E, 0x002F, 0x0030, 0x0031, 0x0032, 0x0033, 0x0034, 0x0035, 0x0036, 0x0037, 0x0038, 0x0039, 0x003A, 0x003B, 0x003C, 0x003D, 0x003E, 0x003F, 0x00A1, 0x0041, 0x0391, 0x0042, 0x0392, 0x0043, 0x0044, 0x0045, 0x0395, 0x0046, 0x0047, 0x0048, 0x0397, 0x0049, 0x0399, 0x004A, 0x004B, 0x039A, 0x004C, 0x004D, 0x039C, 0x004E, 0x039D, 0x004F, 0x039F, 0x0050, 0x03A1, 0x0051, 0x0052, 0x0053, 0x0054, 0x03A4, 0x0055, 0x0056, 0x0057, 0x0058, 0x03A7, 0x0059, 0x03A5, 0x005A, 0x0396, 0x00C4, 0x00D6, 0x00DC, 0x00A7, 0x00BF, 0x0061, 0x0062, 0x0063, 0x0064, 0x0065, 0x0066, 0x0067, 0x0068, 0x0069, 0x006A, 0x006B, 0x006C, 0x006D, 0x006E, 0x006F, 0x0070, 0x0071, 0x0072, 0x0073, 0x0074, 0x0075, 0x0076, 0x0077, 0x0078, 0x0079, 0x007A, 0x00E4, 0x00F6, 0x00FC, 0x00E0];

        $can_use_gsm338 = true;
        foreach ($this->utf8ToUnicode($message) as $codepoint) {
            if (!in_array($codepoint, $gsm338_codepoints)) {
                $can_use_gsm338 = false;
                break;
            }
        }

        return $can_use_gsm338;
    }

    /**
     * Takes an UTF-8 string and returns an array of ints representing the
     * Unicode characters. Astral planes are supported ie. the ints in the
     * output can be > 0xFFFF. Occurrances of the BOM are ignored. Surrogates
     * are not allowed.
     *
     * Returns false if the input string isn't a valid UTF-8 octet sequence.
     */
    private function utf8ToUnicode(&$str)
    {
        $mState = 0;     // cached expected number of octets after the current octet
        // until the beginning of the next UTF8 character sequence
        $mUcs4 = 0;     // cached Unicode character
        $mBytes = 1;     // cached expected number of octets in the current sequence

        $out = array();

        $len = strlen($str);
        for ($i = 0; $i < $len; ++$i) {
            $in = ord($str[$i]);
            if (0 == $mState) {
                // When mState is zero we expect either a US-ASCII character or a
                // multi-octet sequence.
                if (0 == (0x80 & ($in))) {
                    // US-ASCII, pass straight through.
                    $out[] = $in;
                    $mBytes = 1;
                } elseif (0xC0 == (0xE0 & ($in))) {
                    // First octet of 2 octet sequence
                    $mUcs4 = ($in);
                    $mUcs4 = ($mUcs4 & 0x1F) << 6;
                    $mState = 1;
                    $mBytes = 2;
                } elseif (0xE0 == (0xF0 & ($in))) {
                    // First octet of 3 octet sequence
                    $mUcs4 = ($in);
                    $mUcs4 = ($mUcs4 & 0x0F) << 12;
                    $mState = 2;
                    $mBytes = 3;
                } elseif (0xF0 == (0xF8 & ($in))) {
                    // First octet of 4 octet sequence
                    $mUcs4 = ($in);
                    $mUcs4 = ($mUcs4 & 0x07) << 18;
                    $mState = 3;
                    $mBytes = 4;
                } elseif (0xF8 == (0xFC & ($in))) {
                    /* First octet of 5 octet sequence.
                     *
                     * This is illegal because the encoded codepoint must be either
                     * (a) not the shortest form or
                     * (b) outside the Unicode range of 0-0x10FFFF.
                     * Rather than trying to resynchronize, we will carry on until the end
                     * of the sequence and let the later error handling code catch it.
                     */
                    $mUcs4 = ($in);
                    $mUcs4 = ($mUcs4 & 0x03) << 24;
                    $mState = 4;
                    $mBytes = 5;
                } elseif (0xFC == (0xFE & ($in))) {
                    // First octet of 6 octet sequence, see comments for 5 octet sequence.
                    $mUcs4 = ($in);
                    $mUcs4 = ($mUcs4 & 1) << 30;
                    $mState = 5;
                    $mBytes = 6;
                } else {
                    /* Current octet is neither in the US-ASCII range nor a legal first
                     * octet of a multi-octet sequence.
                     */
                    return false;
                }
            } else {
                // When mState is non-zero, we expect a continuation of the multi-octet
                // sequence
                if (0x80 == (0xC0 & ($in))) {
                    // Legal continuation.
                    $shift = ($mState - 1) * 6;
                    $tmp = $in;
                    $tmp = ($tmp & 0x0000003F) << $shift;
                    $mUcs4 |= $tmp;

                    if (0 == --$mState) {
                        /* End of the multi-octet sequence. mUcs4 now contains the final
                         * Unicode codepoint to be output
                         *
                         * Check for illegal sequences and codepoints.
                         */

                        // From Unicode 3.1, non-shortest form is illegal
                        if (((2 == $mBytes) && ($mUcs4 < 0x0080)) ||
                            ((3 == $mBytes) && ($mUcs4 < 0x0800)) ||
                            ((4 == $mBytes) && ($mUcs4 < 0x10000)) ||
                            (4 < $mBytes) ||
                            // From Unicode 3.2, surrogate characters are illegal
                            (0xD800 == ($mUcs4 & 0xFFFFF800)) ||
                            // Codepoints outside the Unicode range are illegal
                            ($mUcs4 > 0x10FFFF)
                        ) {
                            return false;
                        }
                        if (0xFEFF != $mUcs4) {
                            // BOM is legal but we don't want to output it
                            $out[] = $mUcs4;
                        }
                        //initialize UTF8 cache
                        $mState = 0;
                        $mUcs4 = 0;
                        $mBytes = 1;
                    }
                } else {
                    /* ((0xC0 & (*in) != 0x80) && (mState != 0))
                     *
                     * Incomplete multi-octet sequence.
                     */
                    return false;
                }
            }
        }

        return $out;
    }

    private function __checkEmptyTelephones(array $telephones)
    {
        $emptys = false;
        foreach ($telephones as $telephone) {
            if (empty($telephone)) {
                $emptys = true;
            }
        }

        return $emptys;
    }
}

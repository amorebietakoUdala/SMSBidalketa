<?php

namespace App\Controller;

use App\DTO\ContactDTO;
use App\Entity\Contact;
use App\Entity\Label;
use App\Form\ContactImportType;
use App\Form\ContactType;
use App\Repository\ContactRepository;
use App\Repository\LabelRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use League\Csv\Reader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/{_locale}")
 */
class ContactController extends AbstractController
{
    private $em;
    private $labelRepo;

    public function __construct(EntityManagerInterface $em, LabelRepository $labelRepo)
    {
        $this->em = $em;
        $this->labelRepo = $labelRepo;
    }

    /**
     * @Route("/contacts/import", name="contact_import")
     */
    public function importAction(Request $request, ContactRepository $repo)
    {
        $form = $this->createForm(ContactImportType::class, null);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $already_existing_contacts = [];
            $repeated_contacts = [];
            $invalid_contacts = [];
            $contacts_without_repeated = [];
            try {
                $file = $form['file']->getData();
                $separator = $this->__detectSeparator($file);
                if (null === $separator) {
                    $this->addFlash('error', 'The csv file has an incorrect number of columns or invalid separator. File csv must have \'telephone;name;surname1;surname2\' without headers.');

                    return $this->render('contact/upload.html.twig', [
                        'form' => $form->createView(),
                        'message' => null,
                        'already_existing_contacts' => null,
                        'repeated_contacts' => null,
                        'contacts_without_repeated' => null,
                    ]);
                }
                $records = $this->__readRecordsFromFile($file, $separator);
                foreach ($records as $record) {
                    $contactDTO = new ContactDTO();
                    $contactDTO->extractFromArray($record);
                    $contact = $repo->findOneBy(['telephone' => $contactDTO->getTelephone()]);
                    if (null == $contact) {
                        /* Telephone not found */
                        $contact = new Contact();
                        $contactDTO->fill($contact);
                        if (!preg_match("/^(71|72|73|74)\d{7}+$|^6\d{8}+$/", $contactDTO->getTelephone())) {
                            $invalid_contacts[] = $contact;
                            continue;
                        }
                    } else {
                        $already_existing_contacts[] = $contact;
                    }
                    if (!array_key_exists($contact->getTelephone(), $contacts_without_repeated)) {
                        $contacts_without_repeated[$contact->getTelephone()] = $contact;
                    } else {
                        $repeated_contacts[$contact->getTelephone()] = $contact;
                    }
                }
                $labels = $form['labels']->getData();
                $this->__assignLabelsToContacts(array_values($contacts_without_repeated), $labels);
                $this->em->flush();
                $this->addFlash('success', 'File successfully processed');
                $message = '';
                if (count($already_existing_contacts) > 0) {
                    $this->addFlash('warning', 'There are already existing contacts. the label will be applied but the contact information has not be updated.');
                }
                if (count($repeated_contacts) > 0) {
                    $this->addFlash('warning', 'There are repeated contacts.');
                }
            } catch (Exception $e) {
                $this->addFlash('error', 'there was an error processing file %message%');
                $message = $e->getMessage();
            }
            $this->__moveProcessedFile($file);

            return $this->render('contact/upload.html.twig', [
                'form' => $form->createView(),
                'message' => $message,
                'already_existing_contacts' => $already_existing_contacts,
                'repeated_contacts' => $repeated_contacts,
                'contacts_without_repeated' => $contacts_without_repeated,
                'invalid_contacts' => $invalid_contacts,
            ]);
        }

        return $this->render('contact/upload.html.twig', [
            'form' => $form->createView(),
            'message' => null,
        ]);
    }

    /**
     * @Route("/contacts", name="contact_list")
     */
    public function listAction(Request $request, ContactRepository $repo)
    {
        $contacts = [];
        $form = $this->createForm(\App\Form\ContactSearchType::class, new ContactDTO());
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /* @var ContactDTO $data */
            $data = $form->getData();
            $contact = new Contact();
            $data->fill($contact);
            $contacts = $repo->findByExample($contact);
        }

        return $this->render('contact/list.html.twig', [
            'form' => $form->createView(),
            'contacts' => $contacts,
        ]);
    }

    /**
     * @Route("/contact/new", name="contact_new")
     */
    public function newAction(Request $request, ContactRepository $repo)
    {
        $form = $this->createForm(ContactType::class, new ContactDTO());
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /* @var $data ContactDTO */
            $data = $form->getData();
            $exists = $repo->findOneBy([
                'telephone' => $data->getTelephone(),
            ]);
            if ($exists) {
                $this->addFlash('error', 'Duplicate contact');

                return $this->render('/contact/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
            $labels = $data->getLabels();
            $this->__removeCollectionDuplicates($labels, $this->__removeLabelDuplicates($labels));
            $contact = new Contact();
            $data->fill($contact);
            $this->em->persist($contact);
            $this->em->flush();
            $this->addFlash('success', 'Contact saved');

            return $this->redirectToRoute('contact_list');
        }

        return $this->render('contact/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/contact/{contact}/edit", name="contact_edit")
     */
    public function editAction(Request $request, Contact $contact, ContactRepository $repo)
    {
        $contactDTO = new ContactDTO($contact);

        $originalTelephone = $contact->getTelephone();
        $form = $this->createForm(ContactType::class, $contactDTO, []);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /* @var $data ContactDTO */
            $data = $form->getData();
            $telephone = $data->getTelephone();
            $contacts = $repo->findBy(['telephone' => $telephone]);
            if (count($contacts) && $originalTelephone !== $telephone) {
                $this->addFlash('error', 'Repeated telephone');
                return $this->render('contact/edit.html.twig', [
                    'form' => $form->createView(),
                    'readonly' => false,
                    'new' => false,
                ]);
            }
            $labels = $data->getLabels();
            $this->__removeCollectionDuplicates($labels, $this->__removeLabelDuplicates($labels));
            $data->fill($contact);
            $this->em->persist($contact);
            $this->em->flush();
            $this->addFlash('success', 'Contact saved');

            return $this->redirectToRoute('contact_list');
        }

        return $this->render('contact/edit.html.twig', [
            'form' => $form->createView(),
            'readonly' => false,
            'new' => false,
        ]);
    }

    /**
     * @Route("/contact/{contact}", name="contact_show")
     */
    public function showAction(Contact $contact)
    {
        $contactDTO = new ContactDTO($contact);

        $form = $this->createForm(ContactType::class, $contactDTO, []);

        return $this->render('contact/edit.html.twig', [
            'form' => $form->createView(),
            'readonly' => true,
            'new' => false,
        ]);
    }

    /**
     * @Route("/contact/{contact}/delete", name="contact_delete")
     */
    public function deleteAction(Contact $contact)
    {
        $this->em->remove($contact);
        $this->em->flush();
        $this->addFlash('success', 'Contact deleted');

        return $this->redirectToRoute('contact_list');
    }

    /**
     * Return the labels array without duplicates.
     *
     * @var
     *
     * @return array $uniqueArray
     */
    private function __removeLabelDuplicates($labels)
    {
        $uniqueArray = [];
        foreach ($labels as $label) {
            if (!in_array($label->getName(), $uniqueArray)) {
                $uniqueArray[] = $label->getName();
            }
        }

        return $uniqueArray;
    }

    private function __removeCollectionDuplicates($labels, $uniques)
    {
        $labels->clear();
        foreach ($uniques as $unique) {
            $label = $this->labelRepo->findOneBy(['name' => $unique]);
            if (null === $label) {
                $label = new Label();
                $label->setName($unique);
                $labels->add($label);
            } else {
                $labels->add($label);
            }
        }

        return $labels;
    }

    private function __readRecordsFromFile($file, $separator = ';')
    {
        $reader = Reader::createFromPath($file, 'r');
        $reader->addStreamFilter('convert.iconv.ISO-8859-15/UTF-8');
        $reader->setDelimiter($separator);
        //                $reader->setHeaderOffset(0);
        $records = $reader->getRecords();

        return $records;
    }

    private function __moveProcessedFile($file)
    {
        $date = new DateTime();
        $dateStr = $date->format('Ymdhis');
        $filename = preg_replace('/\\.[^.\\s]{3,4}$/', '', $file->getClientOriginalName());
        $directory = $this->getParameter('uploads_directory');
        $extension = $file->guessExtension();
        if (!$extension) {
            // extension cannot be guessed
            $extension = 'bin';
        }
        $file->move($directory, $dateStr . '-' . $filename . '.' . $extension);
    }

    private function __assignLabelsToContacts($contacts, $labels)
    {
        foreach ($contacts as $contact) {
            foreach ($labels as $label) {
                $label2 = $this->labelRepo->findOneBy(['name' => $label->getName()]);
                if (null === $label2) {
                    $contact->addLabel($label);
                } else {
                    $contact->addLabel($label2);
                }
            }
            $this->em->persist($contact);
        }
    }

    private function __detectSeparator($file)
    {
        $separators = [';', ',', "\t"];
        $line = fgets(fopen($file, 'r'));
        foreach ($separators as $separator) {
            if (3 === substr_count($line, $separator)) {
                return $separator;
            }
        }

        return null;
    }
}

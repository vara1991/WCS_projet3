<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Form\ParticipantType;
use App\Repository\ParticipantRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

class ParticipantController extends AbstractController
{
    // The SessionInterface save the participant in global var session
    /**
     * @var SessionInterface
     */
    private $session;

    private $mailer;

    private $participantRepository;

    public function __construct(SessionInterface $sessionParticipant, ParticipantRepository $participantRepository, MailerInterface $mailer)
    {
        $this->session = $sessionParticipant;
        $this->mailer = $mailer;
        $this->participantRepository = $participantRepository;
    }

    //this function flush the participant data and save the id of this participant in SessionInterface
    /**
     * @Route("/participant/{id}", name="participant")
     * @param Request $request
     * @return Response
     */
    public function participant(Request $request, User $user):Response
    {
        //These three lines correspond to the connection of a participant
        if ($this->session->get('connection') == true){
            $connection = true;
        }else{
            $connection = false;
        }

        $participant = new Participant();
        $form = $this->createForm(ParticipantType::class, $participant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $participant->setSession($user->getSession());
            $participant->setCompany($user->getSession()->getCompany());
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($participant);
            $entityManager->flush();
            $this->session->set('id', $participant->getId());

            return $this->redirectToRoute('evaluation', [
                'id' => $user->getId()
            ]);
        }

        return $this->render('Form/participant.html.twig', [
            'participant' => $participant,
            'form' => $form->createView(),
            'connection' => $connection
        ]);
    }

    //this function generate the certificate of this participant with Ajax on the next page("Evaluation-Page")
    /**
     * @Route("/ajax-generate-attestation/{id}", name="ajax-generate-attestation",  methods={"GET", "POST"}))
     * @param Participant $participant
     * @return JsonResponse
     */
    public function generateAttestation(Participant $participant)
    {
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($pdfOptions);
        $company = $participant->getSession()->getCompany();
        $training = $participant->getSession()->getTraining();
        $html = $this->renderView('pdf/attestation.html.twig', [
            'company' => $company,
            'participant' => $participant,
            'training' => $training
        ]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $output = $dompdf->output();
        $pdfFilepath = 'assets/documents/attestations/attestation'.$participant->getFirstname().$participant->getLastname().$participant->getId().'.pdf';
        file_put_contents($pdfFilepath, $output);

        return $this->json(['message' => 'L\'attestation a bien été générée'], 200);
    }

    //this function send an email at this participant with his certificate with Ajax on the next page("Evaluation-Page")
    /**
     * @Route("/ajax-send-mail/{id}", name="ajax-send-mail",  methods={"GET", "POST"}))
     * @param Participant $participant
     * @return JsonResponse
     * @throws TransportExceptionInterface
     */
    public function sendMailToParticipant(Participant $participant)
    {
        $email = (new TemplatedEmail())
            //->from('mafomation.life@gmail.com')
            //->to($participant->getEmail())
            //->cc('mafomation.life@gmail.com')
            ->from('sten.test4php@gmail.com')
            ->to('sten.test4php@gmail.com')
            ->subject('Votre attestation de formation LUF/SCHILLER')
            ->htmlTemplate('Home/email/attestation-email.html.twig')
            ->context(['contact' => $participant])
            ->attachFromPath('assets/documents/attestations'.'/attestation'.$participant->getFirstname().$participant->getLastname().$participant->getId().'.pdf');
        $this->mailer->send($email);

        return $this->json(['message' => 'L\'attestation a bien été envoyée'], 200);
    }
}

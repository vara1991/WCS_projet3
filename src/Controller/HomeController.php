<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Form\ContactType;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    // The SessionInterface save the participant in global var session
    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(SessionInterface $sessionParticipant)
    {
        $this->session = $sessionParticipant;
    }

    /**
     * @Route("/", name="home_index")
     * @return Response
     */
    public function index(): Response
    {
        //These three lines correspond to the connection of a user
        $connection = false;
        if ($this->session->get('connection') == true){
            $connection = true;
        }
        return $this->render('Home/index.html.twig',[
            'connection' => $connection
        ]);
    }

    //this function send an email at the administrator with the contact form
    /**
     * @Route("/contact", name="contact")
     * @param Request $request
     * @param MailerInterface $mailer
     * @return Response
     * @throws TransportExceptionInterface
     */
    public function contact(Request $request, MailerInterface $mailer) :Response
    {
        //These three lines correspond to the connection of a participant
        $connection = false;
        if ($this->session->get('connection') == true){
            $connection = true;
        }

        $contact = new Contact();
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = (new TemplatedEmail())
                //->from('mafomation.life@gmail.com')
                //->to($contact->getEmail())
                //->cc('mafomation.life@gmail.com')
                ->from('sten.test4php@gmail.com')
                ->to('sten.test4php@gmail.com')
                ->subject($contact->getObject())
                ->htmlTemplate('Home/email/notification.html.twig')
                ->context(['contact' => $contact]);
            $mailer->send($email);
            $this->addFlash('success', 'Votre mail a bien été envoyé !');

            return $this->redirectToRoute('home_index');
        }

        return $this->render('Home/contact.html.twig', [
            'form' => $form->createView(),
            'connection' => $connection,
        ]);
    }

    //this function show the Memento
    /**
     * @Route("/memento", name="memento")
     * @return Response
     */
    public function memento(): Response
    {
        return $this->render('Home/email/memento.html.twig');
    }

    //this function show the legal notice
    /**
     * @Route("/mentions-legales", name="mentions_legales")
     * @return Response
     */
    public function mentions_legales(): Response
    {
        $connection = false;
        return $this->render('Home/mentions_legales.html.twig', [
            'connection' => $connection
        ]);
    }
}
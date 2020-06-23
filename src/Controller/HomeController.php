<?php


namespace App\Controller;


use App\Entity\Contact;
use App\Form\ContactType;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="home_index")
     * @return Response
     */
    public function index(): Response
    {
        return $this->render('Home/index.html.twig');
    }

    /**
     * @Route("/contact", name="contact")
     * @param Request $request
     * @param MailerInterface $mailer
     * @return Response
     * @throws TransportExceptionInterface
     */
    public function contact(Request $request, MailerInterface $mailer) :Response
    {
        $contact = new Contact();
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('success', 'Votre mail à bien été envoyé !');
            $email = (new TemplatedEmail())
                ->from('sten.quidelleur@outlook.fr')
                ->to($contact->getEmail())
                ->subject($contact->getObject())
                ->htmlTemplate('Home/email/notification.html.twig')
                ->context(['contact' => $contact]);
            $mailer->send($email);

            return $this->redirectToRoute('home_index');
        }

        return $this->render('Home/contact.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
<?php

namespace App\Controller;

use App\Entity\Offer;

use App\Entity\Recipient;
use App\Entity\Request as BookingRequest;
use App\Form\SearchOfferType;
use App\Form\SelectDateType;
use App\Repository\AvailabilityOfferRepository;
use App\Repository\OfferRepository;
use App\Service\DateAvailableManager;
use App\Service\EmailManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="app_index")
     */



    public function indexAction(OfferRepository $offerRepository)
    {
        $offers = $offerRepository->getLastOffer();
        dump($offers);
        return $this->render('home/index.html.twig', [
            'offers' => $offers
        ]);
    }

    /**
     * @param Request $request
     * @param OfferRepository $offerRepository
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/recherche", name="app_index_search")
     */
    public function searchAction(Request $request, OfferRepository $offerRepository)

    {
        $form = $this->createForm(SearchOfferType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $request->request->get('search_offer');
            $offers = $offerRepository->searchOffer($data['name'], $data['region']);

            return $this->render('home/index.html.twig', [
                'form' => $form->createView(),
                'offers' => $offers
            ]);
        }
    }

    /**
     * @Route("/offre/{offer}", name="app_index_detail_offer")
     * @param Offer $offer
     * @param Request $request
     * @param DateAvailableManager $dateAvailableManager
     * @param EmailManager $emailManager
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function detailAction(Offer $offer, Request $request, DateAvailableManager $dateAvailableManager, EmailManager $emailManager)
    {
        $dateAvailableManager->getUnbookDate($offer);
        $bookingRequest = new BookingRequest();
        $form = $this->createForm(SelectDateType::class, $bookingRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dateInput = $request->request->get('select_date');
            $dateInterval = $dateAvailableManager->checkBooking($dateInput);

            if (is_null($dateInterval)) {
                $this->addFlash('error', "Cette date n'est pas valide");

                return $this->render('home/detail.html.twig', [
                    'offer' => $offer,
                    'form' => $form->createView()
                ]);
            }

            $this->getUser()->addRequest($bookingRequest);

            $dates = $dateAvailableManager->parseDateInterval($dateInput);

            $bookingRequest->setAvailableOffer($dateInterval);
            $dateInterval->addRequest($bookingRequest);
            $bookingRequest->setStartDate($dates["startDate"]);
            $bookingRequest->setEndDate($dates["endDate"]);

            $em = $this->getDoctrine()->getManager();
            $em->persist($bookingRequest);
            $em->flush();

            $emailManager->sendSendBookingOffer($this->getUser(), $offer->getTitle());


            return $this->redirectToRoute('app_index_booking_offer');
        }

        return $this->render('home/detail.html.twig', [
            'offer' => $offer,
            'form' => $form->createView()
        ]);
    }


    /**
     * @Route("/recherche-resultat/{data]", name="app_search_result")
     * @param Offer $offer
     * @param DateAvailableManager $dateAvailableManager
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAvailableOfferDates(Offer $offer, DateAvailableManager $dateAvailableManager)
    {
        return new JsonResponse([
            'dates' => $dateAvailableManager->getUnbookDate($offer)
        ]);
    }
}

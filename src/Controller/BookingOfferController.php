<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\RequestRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class BookingOfferController extends AbstractController
{
    /**
     * @Security("has_role('ROLE_CLIENT')")
     * @Route("/client/booking/offer", name="app_index_booking_offer")
     */
    public function index(RequestRepository $requestRepository)
    {

        $requests = $requestRepository->findAll();
        return $this->render('booking_offer/index.html.twig', [
            'controller_name' => 'BookingOfferController',
            'requests' => $requests,
            'status' => User::STATUS
        ]);
    }
}
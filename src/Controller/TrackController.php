<?php

namespace App\Controller;

use App\Exception\InvalidUrlException;
use App\Exception\ProductNotFoundException;
use App\Exception\UnsupportedSiteException;
use App\Tracker\TrackerFactory;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TrackController extends Controller
{
    /**
     * @Route("/api/track", name="track", methods={"get"})
     */
    public function trackAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $serializer = $this->get('serializer');

//        $url = $request->request->get('url');
//        $interval = $request->request->get('interval');

        $url = $request->query->get('url');
        $interval = $request->query->get('interval');

        $error = true;
        $product = null;

        try {
            $tracker = TrackerFactory::createFromURL($url);
            $product = $tracker->fetchProduct();

            $product->setUser($this->getUser());
            $product->setIntervalTime($interval);

            $em->persist($product);
            $em->flush();

            $message = 'The product is tracking now.';
            $error = false;
        }
        catch (InvalidUrlException $e)
        {
            $message = 'URL is invalid!';
        }
        catch (UnsupportedSiteException $e)
        {
            $message = 'This site is not supported!';
        }
        catch (ProductNotFoundException $e)
        {
            $message = 'The product was not found in page!';
        }
        catch (ConnectException $e)
        {
            $message = sprintf("Could not connect host '%s'", parse_url($url)['host']);
        }
        catch (RequestException $e)
        {
            $message = sprintf("Could not send request host '%s'", parse_url($url)['host']);
        }
        catch (\Exception $e)
        {
            $message = 'An error occurred.';
            if ($this->getParameter('kernel.debug'))
                throw $e;
        }

        return new JsonResponse([
            ($error ? 'error' : 'success') => $message,
            'product' => $error ? null : $serializer->normalize($product),
        ]);
    }
}
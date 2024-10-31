<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

declare(strict_types=1);

namespace BaksDev\Captcha\Controller;


use BaksDev\Captcha\BaksDevCaptchaBundle;
use BaksDev\Core\Controller\AbstractController;
use Random\Randomizer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class ImageController extends AbstractController
{
    #[Route('/captcha/image', name: 'captcha.image', methods: ['GET', 'POST'])]
    public function index(
        #[Autowire(env: 'APP_SECRET')] string $SECRET,
        Request $request,

    ): Response
    {
        $Randomise = new Randomizer();

        $Session = $request->getSession();

        $captchaCounter = $request->getClientIp().'captcha_counter';

        /** Сбрасываем счетчик неправильного ввода в течении 5 минут */
        if((time() - $Session->getMetadataBag()->getLastUsed()) > 300)
        {
            $Session->remove($captchaCounter);
        }

        /** По умолчанию количество цифр - 4 */
        $length = $Session->get($captchaCounter) ?: 4;

        7 > $length ?: $length = 7;


        $bg = BaksDevCaptchaBundle::PATH.implode(DIRECTORY_SEPARATOR, ['Resources', 'captcha', 'bg'.$Randomise->getInt(0, 7).'.png']);
        $font = BaksDevCaptchaBundle::PATH.implode(DIRECTORY_SEPARATOR, ['Resources', 'captcha', 'captcha'.$Randomise->getInt(0, 5).'.ttf']);

        $image = imagecreatefrompng($bg);

        $code = '';

        $x = match (true)
        {
            $length === 4 => 50,
            $length === 5 => 30,
            $length === 6 => 20,
            default => 10
        };

        $gray = imagecolorallocate($image, 200, 200, 200); // цвет по умолчанию
        $grayKey = $Randomise->getInt(1, $length);

        $chars = '1234567890';

        for($i = 1; $i <= $length; $i++)
        {
            $size = $Randomise->getInt(15, 35);

            $chars = $Randomise->shuffleBytes($chars);
            $char = $Randomise->getBytesFromString($chars, 1);

            $code .= $char;

            $angle = $Randomise->getInt(-30, 30);
            $y = 50;

            $color = $gray;

            if($grayKey !== $i)
            {
                $red = $Randomise->getInt(50, 200);
                $green = $Randomise->getInt(50, 200);
                $blue = $Randomise->getInt(50, 200);

                $color = imagecolorallocate($image, $red, $green, $blue);

                if($color === false)
                {
                    $color = $gray;
                }
            }

            imagefttext($image, $size, $angle, $x, $y, $color, $font, $char);

            $x += $Randomise->getInt(15, 20);

        }

        $response = new StreamedResponse(fn() => imagepng($image), 200);

        $Session->set($request->getClientIp().'captcha', crypt($code, $SECRET));
        $Session->save();

        $response->headers->set('Cache-Control', 'no-store, must-revalidate');
        $response->headers->set('Expires', '0');
        $response->headers->set('Content-Type', 'image/png');

        return $response;

    }
}

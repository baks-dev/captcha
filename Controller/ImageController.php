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


use BaksDev\Core\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class ImageController extends AbstractController
{
    #[Route('/image/captcha', name: 'captcha.image', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        int $page = 0,
    ): Response
    {
        $Session = $request->getSession();


        // 1. Генерируем код капчи
        // 1.1. Устанавливаем символы, из которых будет составляться код капчи
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcdefghijklmnopqrstuvwxyz';
        // 1.2. Количество символов в капче
        $length = 6;

        // 1.3. Генерируем код
        $code = substr(str_shuffle($chars), 0, $length);


        $Session->set('captcha', crypt($code, 'FYfTQPHtSWXWAa'));
        $Session->save();

        //        if (USE_SESSION) {
        //            // 2a. Используем сессию
        //            session_start();
        //            $_SESSION['captcha'] =  crypt($code, '$1$itchief$7');
        //            session_write_close();
        //        } else {
        //            // 2a. Используем куки (время действия 600 секунд)
        //            $value = crypt($code, '$1$itchief$7');
        //            $expires = time() + 600;
        //            setcookie('captcha', $value, $expires, '/', 'test.ru', false, true);
        //        }

        // 3. Генерируем изображение
        // 3.1. Создаем новое изображение из файла
        $image = imagecreatefrompng(__DIR__.'/files/bg.png');
        // 3.2 Устанавливаем размер шрифта в пунктах
        $size = 36;
        // 3.3. Создаём цвет, который будет использоваться в изображении
        $color = imagecolorallocate($image, 66, 182, 66);
        // 3.4. Устанавливаем путь к шрифту
        $font = __DIR__.'/files//oswald.ttf';
        // 3.5 Задаём угол в градусах
        $angle = rand(-10, 10);
        // 3.6. Устанавливаем координаты точки для первого символа текста
        $x = 56;
        $y = 64;

        // 3.7. Наносим текст на изображение
        imagefttext($image, $size, $angle, $x, $y, $color, $font, $code);


        $response = new StreamedResponse(fn() => imagepng($image), 200);

        $response->headers->set('Cache-Control', 'no-store, must-revalidate');
        $response->headers->set('Expires', '0');
        $response->headers->set('Content-Type', 'image/png');

        // 3.9. Выводим изображение
        //imagepng($image);

        // 3.10. Удаляем изображение
        //imagedestroy($image);

        return $response;

    }
}

<?php
/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Captcha\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class CaptchaVerify implements CaptchaVerifyInterface
{
    public function __construct(
        #[Autowire(env: 'APP_SECRET')] private string $SECRET,
        private RequestStack $request
    ) {}

    public function verify(string $code): bool
    {
        $request = $this->request->getCurrentRequest();

        if($request === null)
        {
            return false;
        }

        $Session = $this->request->getSession();


        $name = $request->getClientIp().'captcha';

        $value = $Session->get($name);
        $Session->remove($name);

        $code = crypt(trim($code), $this->SECRET);

        if($code === $value)
        {
            return true;
        }

        /** Добавляем счетчик неправильного ввода */

        $captchaCounter = $request->getClientIp().'captcha_counter';

        $length = $Session->get($captchaCounter) ?: 4;

        if(7 > $length)
        {
            ++$length;
        }

        $Session->set($captchaCounter, $length);
        $Session->save();


        $Session
            ->getFlashBag()
            ->add('Проверочный код', 'Введите проверочный код, указанный на картинке');

        return false;

    }
}
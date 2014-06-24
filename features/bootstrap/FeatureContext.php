<?php
/**
 * @author Mustafa Hasturk
 * @site http://github.com/muhasturk
 */
use Behat\MinkExtension\Context\MinkContext;

class FeatureContext extends MinkContext
{
    public $base_url;
    public $exception_message = '';
    public $warning_message = '';
    private $mail_message = '';
    public $mailSubject = 'BDD Report';

    private $totalProduct;
    private $subProduct;
    private $totalProvider;
    private $totalBrand;

    protected $now;
    private $session;
    private $page;

    function __construct()
    {
        $this->base_url = "http://vitringez.com/";
        $this->setTime();
    }

    private function initSession()
    {
        $this->session = $this->getSession();
        $this->page = $this->session->getPage();
    }

    /**
     * @Then /^I mix some filter$/
     */
    private function sendMail()
    {
        /**
         * You have to setup PHPMailer to use this method
         * @link https://github.com/PHPMailer/PHPMailer
         */
        $this->setNoProblemStatus();

        $mail = new PHPMailer;
        $mail->isSMTP();
        $mail->FromName = 'Mustafa Hasturk';
        $mail->addAddress('tzzzf@droplar.com', 'muhasturk');
        $mail->WordWrap = 50;
        $mail->isHTML(true);
        $mail->Subject = $this->mailSubject;
        $mail->Body = $this->setMailBody();
        $mail->AltBody = $this->setMailAltBody();

        echo((!$mail->send()) ? "Message could not be sent.\n 'Mailer Error: ' . $mail->ErrorInfo . \n" :
            "Message has been sent\n");
    }

    private function setNoProblemStatus()
    {
        if (empty($this->exception_message))
            $this->exception_message = 'There is no exception';
        if (empty($this->warning_message))
            $this->warning_message = 'No warning';
    }

    private function setMailBody()
    {
        return <<<DOC
        <!DOCTYPE html>
        <html>
            <head>
                <title> Report </title>
                <meta charset='utf-8'>
            </head>
            <body>
                <header>
                    <p> generated on {$this->now->format('Y-m-d H:i:s')} </p>
                </header>

                <div id='container'>

                    <section id='exception'>
                    <h1> Exception </h1>
                    $this->exception_message;
                    </section>

                    <hr>
                    <section id='warning'>
                    <h2> Warning </h2>
                    $this->warning_message;
                    </section>

                    <hr><section id='report'>
                    <h3> BDD Test Report </h3>
                    $this->mail_message;
                    </section>
                </div>
                <footer>
                    <p> created by muhasturk </p>
                </footer>
            </body>
        </html>
DOC;

    }

    private function setMailAltBody()
    {
        return <<<ALT
        <strong>\n You have to get modern mail client! \n</strong>\n
ALT;
    }

    /**
     * @Given /^I send report mail$/
     */
    public function iSendReportMail()
    {
        $this->mailSubject .= "_" . $this->now->getTimestamp();
        $this->sendMail();
    }

    private function getFilterProgressBar()
    {
        $progressBar = $this->page->findById("filterProgressBar");
        if (!is_object($progressBar))
            $this->setException('filterProgressBar');
        return $progressBar->getText();
    }

    /**
     * @When /^I check "([^"]*)" sort algorithm$/
     */
    public function iCheckSortAlgorithm($alg)
    {
        $this->mailSubject = 'SortPrice Feature';
        try {
            $this->initSession();
            $url = $this->setAlgorithm($alg);
            $this->checkAlgorithm($alg, $url);
            $this->session->visit($url);
            $cond = $this->comparePrices($alg);
            $this->getSortAlgorithmResult($cond, $alg);
        } catch (Exception $e) {
            $this->getException($e);
        }
    }

    private function getSortAlgorithmResult($condition, $algorithm)
    {
        $this->mail_message .= "<span class='fail'> $algorithm algorithm has a problem </span><br>";
        if ($condition)
            $this->mail_message = "<span class='ok'> $algorithm algorithm works properly </span><br>\n";
        echo $condition ? "\e[34m'$algorithm' algorithm works properly\n" :
            "'$algorithm' algorithm has a problem!\e[0m\n";
    }

    private function comparePrices($alg)
    {
        $prices = $this->getPrices();
        $sorted = $prices;
        $alg == "descending" ? arsort($sorted) : asort($sorted);
        return boolval($sorted == $prices);
    }


    public function getException($exception)
    {
        $this->exception_message .= "\n" . $exception->getMessage();
        $this->iSendReportMail();
        throw new Exception($this->exception_message);
    }


    private function getPrices()
    {
        $prices_em = [];
        for ($i = 3; $i < 27; $i++)
            /** @var array $prices_em */
            $prices_em = $this->page->find('css',
                "#catalogResult > div > div > div:nth-child($i) > div.productDetail > a > span.prices > em.new");
        $prices = [];
        foreach ($prices_em as $d)
            $prices[] = (float)str_replace(",", "", $d->getText());
        return $prices;
    }

    private function setAlgorithm($alg)
    {
        switch ($alg) {
            case "ascending":
                $sort_url = "arama?sort=price|asc";
                break;
            case "descending":
                $sort_url = "arama?sort=price|desc";
                break;
            default:
                $sort_url = "arama";
                break;
        }
        return $this->base_url . $sort_url;
    }

    private function checkAlgorithm($alg, $url)
    {
        if ($url == ($this->base_url . 'arama')) {
            $this->warning_message .= "<span class='warning'>
                There is no sorting algorithm called '$alg' on the site <br>
                Check test algorithm in .feature file </span>\n";
            throw new Exception('Check test algorithm in .feature file');
        }
    }

    /**
     * @When /^I fill profile details$/
     */
    public function iFillProfileDetails()
    {
        $this->mail_message = "<strong class='test_feature'> Profile Detail Feature </strong> ";
        $this->mailSubject = 'ProfileDetails Report';
        try {
            $this->initSession();

            $this->page->find('css', '#vitringez_user_profile_form_biography')
                ->setValue($this->generateRandomString(16));
            $this->page->find('css', '#vitringez_user_profile_form_city')
                ->setValue($this->generateRandomString(7));
            $this->page->find('xpath', '//*[@id="vitringez_user_profile_form_newsletterSubscribe"]')
                ->uncheck();

            $this->mail_message .= "\n<span class='ok'>profile details test ok</span>";

        } catch (Exception $e) {
            $this->getException($e);
        }
    }

    /**
     * @When /^I scan "([^"]*)" category$/
     */
    public function iScanCategory($category)
    {
        $this->mailSubject = "ScanCategory Report";
        try {
            $this->initSession();
            $this->session->visit($this->setUrl($category));
            $this->setGeneralVariable();
            $this->setGeneralInfo();
            $this->scanProviders($this->getProvidersORBrands('providers'));
        } catch (Exception $e) {
            $this->getException($e);
        }
    }

    private function scanProviders($providersDiv)
    {
        $this->mail_message .= "<div class='providers'>\n";
        for ($i = 1; $i < $this->totalProvider; $i++) {
            $providerDataName = $this->getProviderDataName($providersDiv[$i]);
            $providerSpan = $providersDiv[$i]->find('css', 'span');
            $subProductText = $providerSpan->getText();
            $this->subProduct = intval(str_replace('(', '', $subProductText));
            $this->mail_message .= "<div class='provider'>'$providerDataName' de/da : {$this->checkSubProduct()}</div>\n";
        }
        $this->mail_message .= "</div>\n";
    }

    private function checkSubProduct()
    {
        $sp = "<span class='fail'> ürün yok. </span>";
        if ($this->subProduct > 0)
            $sp = "<span class='ok'> '$this->subProduct' ürün var. </span>";
        return $sp;
    }

    private function getProviderDataName($provider)
    {
        $providerInput = $provider->find('css', 'input');
        return $providerInput->getAttribute('data-name');
    }

    private function getProvidersORBrands($what)
    {
        switch ($what) {
            case 'providers':
                $path = '#filterProvider';
                break;
            case 'brands':
                $path = '#filterBrands';
                break;
            default:
                throw new Exception("getProviderORBrands method only supports providers or brands parameters");
        }
        $obj = $this->page->find('css', $path . ' > div > div > div')->findAll('css', 'div');
        $this->checkProvidersORBrands($obj);
        return $obj;
    }

    private function checkProvidersORBrands($what)
    {
        if(count($what) <=0)
            throw new Exception("There is no providers | brands on site");
    }

    private function setException($obj)
    {
        $this->exception_message .= "<span class='exception'> __! Check '$obj' path | id | attribute !__ </span>";
        throw new Exception($this->exception_message);
    }

    private function setGeneralVariable()
    {
        $this->totalProduct = intval($this->getFilterProgressBar());
        $this->totalProvider = count($this->getProvidersORBrands('providers'));
        $this->totalBrand = count($this->getProvidersORBrands('brands'));
    }

    private function setGeneralInfo()
    {
        $this->mail_message .= <<<INFO
        <div id='general'>\n
        <span class='totalProduct'> Toplam ürün: {$this->totalProduct} </span><br>\n
        <span class='totalProvider'> Provider sayısı: {$this->totalProvider} </span><br>\n
        <span class='totalBrands'> Brand sayısı:  {$this->totalBrand} </span><br>\n
        </div>\n
INFO;
    }

    private function setUrl($category)
    {
        switch ($category) {
            case "kadın":
                $data_url = "kadin";
                break;
            case "erkek":
                $data_url = "erkek";
                break;
            case "çocuk":
                $data_url = "cocuk";
                break;
            case "ev":
                $data_url = "ev";
                break;
            default:
                $data_url = 'arama';
                break;
        }
        return $this->base_url . $data_url;
    }

    /**
     * @When /^I set the discount alert$/
     */
    public function iSetTheDiscountAlert()
    {
        $session = $this->getSession();
        $page = $session->getPage();

        $session->visit("http://www.vitringez.com/urun/bisous-rose-metalik-canta-207258");
        $page->find("xpath", '//*[@id="content"]/div[1]/div/div[2]/a[2]')->click();

        for ($i = 1; $i <= 4; $i++) {
            $page->find("xpath", '//*[@id="simplemodal-data"]/form/div/label[' . $i . ']/input')->check();
        }
        $page->find("xpath", '//*[@id="simplemodal-data"]/form/input[1]')->click();

    }

    /**
     * @When /^I set the fashion alert$/
     */
    public function iSetTheFashionAlert() //ok
    {
        $this->mail_message = "<strong class='test_feature'> Fashiın Akert </strong><br>\n ";
        $this->mailSubject = 'FashionnAlert Report';
        try {
            $this->initSession();
            $this->session->visit($this->getXProduct(1)['data-uri']);
            $this->page->find('css', '#content > div.productDetail > div > div.productButtons > a.gradient.fashionAlert')
                ->click();
            $this->checkFashionInputs();
            $this->submitFashionAlert();
            $this->mail_message .= "<span class='ok'> 'FashionAlert' set successfully </span>";
        } catch (Exception $e) {
            $this->getException($e);
        }
    }

    private function submitFashionAlert()
    {
        $this->page->find('xpath', '//*[@id="simplemodal-data"]/form/input[1]')
            ->click();
    }

    private function checkFashionInputs()
    {
        for ($i = 1; $i <= 3; $i++)
            $this->page->find('xpath', '//*[@id="simplemodal-data"]/form/div/label[' . $i . ']/input')
                ->check();
    }


    private function getXProduct($index)
    {
        $index +=3;
        $firstProduct = $this->page->find('xpath', "//*[@id='catalogResult']/div/div/div[$index]");

        if (!is_object($firstProduct))
            $this->setException('firstProduct');

        if (!$firstProduct->hasAttribute('data-uri'))
            $this->setException('firstProduct_data-uri');

        return ['firstProduct' => $firstProduct,
            'data-uri' => $firstProduct->getAttribute('data-uri')];
    }

    private function setTime() //ok
    {
        $this->now = new DateTime();
        $this->now->setTimezone(new DateTimeZone('Europe/Istanbul'));
    }


    /**
     * @When /^I fill in registration form$/
     */
    public function iFillInRegistrationForm() //ok
    {
        $this->mail_message = "<strong class='test_feature' style='color: #990000; font-style: oblique'> Register Test </strong>";
        $this->mailSubject = 'Register Feature Report';

        try {
            $this->initSession();
            $this->runNewUserLink();
            $this->iWaitSecond("3");
            $this->setRegisterInputs( $this->getRegisterInputs() );
            $this->mail_message .= "\n<mark class='ok'>Başarılı bir şekilde üye olundu.</mark>";

        } catch (Exception $e) {
            $this->getException($e);
        }
    }

    private function getRegisterInputs()
    {
        $divRows = $this->page->findAll('css', 'div.row');
        $registerInputs = [];
        for ($i = 0; $i < count($divRows); $i++)
            $registerInputs[] = $divRows[$i]->find('css', 'input');
        return $registerInputs;
    }

    private function runNewUserLink()
    {
        $newUserLink = $this->page->findById("newUserLink");
        if (!is_object($newUserLink))
            $this->setException('newUserLink');
        $newUserLink->click();
    }

    private function setRegisterInputs($inputs)
    {
        $inputs[0]->setValue($this->generateRandomString(rand(3, 12)));
        $inputs[1]->setValue($this->generateRandomString(rand(3, 12)));
        $inputs[2]->setValue($this->generateRandomString(rand(5, 12)));
        $inputs[3]->setValue($this->generateRandomEmail());
        $password = $this->generateRandomString(rand(6, 14));
        $inputs[4]->setValue($password);
        $inputs[5]->setValue($password);
        $inputs[6]->find('css', 'input')->check();
        $inputs[7]->find('css', 'input')->click();
    }


    /**
     * @Given /^I wait "([^"]*)" second$/
     */
    public function iWaitSecond($duration)
    {
        $this->getSession()->wait(intval($duration) * 1000,
            '(0 === jQuery.active && 0 === jQuery(\':animated\').length)');
//        $this->getSession()->wait($duration, '(0 === Ajax.activeRequestCount)');
    }

    public function generateRandomEmail()
    {
        return 'bdd_' . $this->generateRandomString() . '@yahoo.com';
    }

    public function generateRandomString($length = 6)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyz';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    public function iMixSomeFilter()
    {
        $this->mailSubject = 'MixFuture Report';
        try {
            $this->initSession();
            $this->setGeneralVariable();
            $this->setGeneralInfo();

            $providers = $this->getProvidersORBrands('providers');
            $brands = $this->getProvidersORBrands('brands');

            if ($this->totalBrand == 0) {
                $this->exception_message .= "<span class='exception'>__! Check brands path or there is no brand on site !__\n</span>";
                throw new Exception($this->exception_message);            }


            if ($this->totalProduct == 0) {
                $this->exception_message .= "<span class='exception'>__! There is no product on site !__\n</span>";
                throw new Exception($this->exception_message);
            }
            echo "Provider sayısı: <$this->totalProvider>\n Brand sayısı: <$this->totalBrand>\nToplam ürün: $this->totalProduct \n\n";

            $colors_container = $this->page->find('css', '#filterColors > div > div > div > ul');
            if (!is_object($colors_container))
                $this->setException('color_container');
            $colors = $colors_container->findAll('css', 'li');
            if (count($colors) == 0) {
                $this->exception_message .= "<span class='exception'>__! There is no color on site or check path !__\n</span>";
                throw new Exception($this->exception_message);
            }
            // one color
            $acolor = $this->getRandColor($colors);
            $session->visit($this->base_url . $acolor['url']);

            echo "\e[34m=============\nRenk Filtresi\n=============\n\e[0m";
            $this->mail_message .= "\n<h2 id='colorfilter'> Renk Filtresi </h2>\n";

            $product = intval($this->getFilterProgressBar($page));
            echo "'{$acolor['data-name']}' seçili iken <$product> ürün var.\n";
            $this->mail_message .= "<span> '{$acolor['data-name']}' seçili iken '$product' ürün var.</span>\n";

            // more than one color
            $color1 = $this->getRandColor($colors);
            $color2 = $this->getRandColor($colors);
            $session->visit($this->base_url . $color1['data-key'] . "-ve-" . $color2['data-key'] . "-renkli");

            $product = intval(($this->getFilterProgressBar($page)));

            echo "\"" . $color1['data-name'] . "\" ve \"" . $color2['data-name'] . "\" seçili iken <" .
                $product . "> ürün var.\n\n";
            $this->mail_message .= "<span>\"" . $color1['data-name'] . "\" ve \"" . $color2['data-name'] . "\" seçili iken \"" .
                $product . "\" ürün var.</span><br>\n\n";

            // price filter
            $color1 = $this->getRandColor($colors);
            $color2 = $this->getRandColor($colors);
            $session->visit($this->base_url . $color1['data-key'] . "-ve-" . $color2['data-key'] . "-renkli");

            $range_div = $page->find('css', '#filterPrice > div > div.range-slider-input');
            if (!is_object($range_div))
                $this->setException('rangeDiv');
            $range_inputs = $range_div->findAll('css', 'input');
            if (count($range_inputs) == 0)
                $this->setException('randeInputs');

            if (!$range_inputs[0]->hasAttribute('value'))
                $this->setException('ranndeMin');
            $range_min = $range_inputs[0]->getAttribute('value');
            if (!$range_inputs[1]->hasAttribute('value'))
                $this->setException('rangeMax');
            $range_max = $range_inputs[1]->getAttribute('value');

            $min_price = rand($range_min, $range_max);
            $max_price = rand($min_price, $range_max);
            $criteria_url = '?criteria%5Bfacet_price%5D=%5B' . $min_price . '+TO+' . $max_price . '%5D';

            $session->visit($this->base_url . $color1['data-key'] . "-ve-" . $color2['data-key'] . "-renkli" . $criteria_url);

            echo "\e[35m==================\nRenk+Fiyat Filtresi\n==================\n\e[0m";
            $this->mail_message .= "<h3 id='color+price'> Renk+Fiyat Filtresi  </h3>\n";

            $product = intval($this->getFilterProgressBar($page));
            echo "\"" . $color1['data-name'] . "\" ve \"" . $color2['data-name'] . "\" seçili iken, [" .
                $min_price . " - " . $max_price . "] fiyat aralığında: <" .
                $product . "> ürün var.\n\n";
            $this->mail_message .= "<span>\"" . $color1['data-name'] . "\" ve \"" . $color2['data-name'] . "\" seçili iken, [" .
                $min_price . " - " . $max_price . "] fiyat aralığında: \"" .
                $product . "\" ürün var.</span><br>\n\n";

            // brand
            $session->visit($this->base_url . "arama/");
            $brand_attr = $this->getRandBrand($brands);
            $session->visit($this->base_url . $brand_attr['url']);

            echo "\e[36m==============\nMarka Filtresi\n==============\n\e[0m";
            $this->mail_message .= "<h4 id='brandfilter'> Marka Filtresi </h4> ";

            $product = intval($this->getFilterProgressBar($page));
            echo "\"" . $brand_attr['data-name'] . "\" seçili iken: <$product> ürün var.\n";
            $this->mail_message .= "<span> '{$brand_attr['data-name']}' seçili iken: '$product' ürün var.</span><br>\n";

            // more than one brand
            $brand1 = $this->getRandBrand($brands);
            $brand2 = $this->getRandBrand($brands);

            $session->visit($this->base_url . $brand1['data-url'] . "-ve-" . $brand2['url']);
            $product = intval($this->getFilterProgressBar($page));
            echo "\"" . $brand1['data-name'] . "\" ve \"" . $brand2['data-name'] . "\" seçili iken: <" .
                $product . "> ürün var.\n";
            $this->mail_message .= "<span>\"" . $brand1['data-name'] . "\" ve \"" . $brand2['data-name'] . "\" seçili iken \"" .
                $product . "\" ürün var.</span><br>\n";

            // brand + provider
            $session->visit($this->base_url . "arama/");
            $brand_attr = $this->getRandBrand($brands);
            $prov_cont = $page->find("css", "#filterProvider > div > div > div");
            if (!is_object($prov_cont))
                $this->setException('providerContainer');
            $providers = $prov_cont->findAll("css", "div");
            if (count($providers) == 0)
                $this->setException('providers');

//            $fl_provider_name = $fl_provider_url = '';
            for ($i = 0; $i < count($providers); $i++) {
                $provider_span = $providers[$i]->find('css', 'span');
                if (!is_object($provider_span))
                    $this->setException('providerSpan');

                if (intval(str_replace("(", "", ($provider_span->getText())))) { // higher zero
                    $provider_input = $providers[$i]->find('css', 'input');
                    if (!is_object($provider_input))
                        $this->setException('providerInput');

                    if (!($provider_input->hasAttribute("data-url")))
                        $this->setException('providerInput_data-url');
                    $fl_provider_url = $provider_input->getAttribute("data-url") . "-magazasi";

                    if (!($provider_input->hasAttribute("data-name")))
                        $this->setException('providerInput_data-name');
                    $fl_provider_name = $provider_input->getAttribute("data-name");
                    break;
                }
            }

            /*            for ($i = 0; $i < count($providers); $i++) {
                            if (intval(str_replace("(", "", ($providers[$i]->find('css', 'span')->getText())))) {
                                $fl_provider_url = $providers[$i]->find('css', 'input')->getAttribute("data-url") . "-magazasi";
                                $fl_provider_name = $providers[$i]->find('css', 'input')->getAttribute("data-name");
                            }
                        }*/

            $session->visit($this->base_url . $brand_attr['url'] . $fl_provider_url);

            $product = intval($this->getFilterProgressBar($page));
            echo "\"" . $brand_attr['data-name'] . "\" ile \"" . $fl_provider_name . "\" mağazası seçili iken <" .
                $product . "> ürüm var.\n";
            $this->mail_message .= "<span> \"" . $brand_attr['data-name'] . "\" ile  \"" . $fl_provider_name . "\" mağazası seçili iken \"" .
                $product . "\" ürün var.</span><br>\n";

        } catch (Exception $e) {
            $this->getException($e);
        }
    }


    private
    function getRandBrand($brands) //ok
    {
        $brand = $brands[rand(0, (count($brands) - 1))];
        $brand_input = $brand->find("css", "input");
        if (!is_object($brand_input))
            $this->setException('brandsInput');
        $attr = [];
        if (!$brand_input->hasAttribute('data-name'))
            $this->setException('brand_data-name');
        $attr['data-name'] = $brand_input->getAttribute("data-name");

        if (!$brand_input->hasAttribute('data-url'))
            $this->setException('brand_data-url');
        $attr['data-url'] = $brand_input->getAttribute("data-url");
        $attr['url'] = $attr['data-url'] . "-modelleri/";
        return $attr;
    }

    private
    function getRandColor($colors) //ok
    {
        $color = $colors[rand(0, (count($colors) - 1))];
        $attr = [];
        if (!$color->hasAttribute('data-name'))
            $this->setException('color-data-name');
        $attr['data-name'] = $color->getAttribute("data-name");
        if (!$color->hasAttribute('data-key'))
            $this->setException('color-data-key');
        $attr['data-key'] = $color->getAttribute("data-key");
        $attr['url'] = $attr['data-key'] . "-renkli";
        return $attr;
    }
}






<?php

/**
 * Securimage CAPTCHA Class.
 *
 * @version    3.0
 * @package    Securimage
 * @subpackage classes
 * @author     Drew Phillips <drew@drew-phillips.com>
 *
 */

namespace Classes\Image;

class Securimage
{
    // All of the public variables below are securimage options
    // They can be passed as an array to the Securimage constructor, set below,
    // or set from securimage_show.php and securimage_play.php


    /**
     * Renders captcha as a JPEG image
     * @var int
     */
    const SI_IMAGE_JPEG = 1;
    /**
     * Renders captcha as a PNG image (default)
     * @var int
     */
    const SI_IMAGE_PNG = 2;
    /**
     * Renders captcha as a GIF image
     * @var int
     */
    const SI_IMAGE_GIF = 3;

    /**
     * Create a normal alphanumeric captcha
     * @var int
     */
    const SI_CAPTCHA_STRING = 0;
    /**
     * Create a captcha consisting of a simple math problem
     * @var int
     */
    const SI_CAPTCHA_MATHEMATIC = 1;

    /**
     * The width of the captcha image
     * @var int
     */
    public $image_width = 130;
    /**
     * The height of the captcha image
     * @var int
     */
    public $image_height = 30;
    /**
     * The type of the image, default = png
     * @var int
     */
    public $image_type = self::SI_IMAGE_PNG;

    /**
     * The background color of the captcha
     * @var Securimage_Color
     */
    public $image_bg_color = array('#FFFFFF');

    /**
     * The color of the captcha text
     * @var Securimage_Color
     */
    public $text_color = array('#666666');

    public $text_colorr;

    /**
     * The color of the lines over the captcha
     * @var Securimage_Color
     */
    public $line_color = array('#666666');
    /**
     * The color of the noise that is drawn
     * @var Securimage_Color
     */
    public $noise_color = array('#8B0000', '#FFEEE1', '#E1F4FF');

    public $iscale = 5;
    /**
     * How transparent to make the text 0 = completely opaque, 100 = invisible
     * @var int
     */
    public $text_transparency_percentage = 30;
    /**
     * Whether or not to draw the text transparently, true = use transparency, false = no transparency
     * @var bool
     */
    public $use_transparent_text = true;

    /**
     * The length of the captcha code
     * @var int
     */
    public $code_length = 4;
    /**
     * Whether the captcha should be case sensitive (not recommended, use only for maximum protection)
     * @var bool
     */
    public $case_sensitive = false;
    /**
     * 中文字符集
     * @var string
     */
    public $charset_cn = '的一是在不了有和人这中大为上个国我以要他时来用们生到作地于出就分对成会可主发年动同工也能下过子说产种面而方后多定行学法所民得经十三之进着等部度家电力里如水化高自二理起小物现实加量都两体制机当使点从业本去把性好应开它合还因由其些然前外天政四日那社义事平形相全表间样与关各重新线内数正心反你明看原又么利比或但质气第向道命此变条只没结解问意建月公无系军很情者最立代想已通并提直题党程展五果料象员革位入常文总次品式活设及管特件长求老头基资边流路级少图山统接知较将组见计别她手角期根论运农指几九区强放决西被干做必战先回则任取据处队南给色光门即保治北造百规热领七海口东导器压志世金增争济阶油思术极交受联什认六共权收证改清己美再采转更单风切打白教速花带安场身车例真务具万每目至达走积示议声报斗完类八离华名确才科张信马节话米整空元况今集温传土许步群广石记需段研界拉林律叫且究观越织装影算低持音众书布复容儿须际商非验连断深难近矿千周委素技备半办青省列习响约支般史感劳便团往酸历市克何除消构府称太准精值号率族维划选标写存候毛亲快斯院查江眼王按格养易置派层片始却专状育厂京识适属圆包火住调满县局照参红细引听该铁价严';
    /**
     * The character set to use for generating the captcha code
     * @var string
     */
    public $charset = 'ABCDEFGHKMNPRSTUVWYZabcdefghkmnprstuvwyz23456789';
    /**
     * How long in seconds a captcha remains valid, after this time it will not be accepted
     * @var unknown_type
     */
    public $expiry_time = 900;

    /**
     * The session name securimage should use, only set this if your application uses a custom session name
     * It is recommended to set this value below so it is used by all securimage scripts
     * @var string
     */
    public $session_name = null;

    /**
     * The level of distortion, 0.75 = normal, 1.0 = very high distortion
     * @var double
     */
    public $perturbation = 1;
    /**
     * How many lines to draw over the captcha code to increase security
     * @var int
     */
    public $num_lines = 5;
    /**
     * The level of noise (random dots) to place on the image, 0-10
     * @var int
     */
    public $noise_level = 0;
    /**
     * The color of the signature text
     * @var Securimage_Color
     */
    public $signature_color = '#707070';
    /**
     * The path to the ttf font file to use for the signature text, defaults to $ttf_file (AHGBold.ttf)
     * @var string
     */
    public $signature_font;
    /**
     * The type of captcha to create, either alphanumeric, or a math problem<br />
     * Securimage::SI_CAPTCHA_STRING or Securimage::SI_CAPTCHA_MATHEMATIC
     * @var int
     */
    public $captcha_type = self::SI_CAPTCHA_STRING;

    /**
     * The captcha namespace, use this if you have multiple forms on a single page, blank if you do not use multiple forms on one page
     * @var string
     * <code>
     * <?php
     * // in securimage_show.php (create one show script for each form)
     * $img->namespace = 'contact_form';
     *
     * // in form validator
     * $img->namespace = 'contact_form';
     * if ($img->check($code) == true) {
     * echo "Valid!";
     * }
     * </code>
     */
    public $namespace;

    /**
     * The font file to use to draw the captcha code, leave blank for default font AHGBold.ttf
     * @var string
     */
    public $ttf_file;
    /**
     * The directory to scan for background images, if set a random background will be chosen from this folder
     * @var string
     */
    public $background_directory;

    protected $im;
    protected $tmpimg;
    protected $bgimg;
    public $securimage_path = null;
    protected $code;
    protected $code_display;
    protected $captcha_code;
    protected $gdbgcolor;
    protected $gdtextcolor;
    protected $gdlinecolor;
    protected $gdsignaturecolor;

    /**
     * 字体显示比率
     * @var string
     */
    public $text_scale = 0.5;
    /**
     * 间距补偿值
     * @var int
     */
    public $offset_x = 23;
    /**
     * 使用中文
     * @var bool
     */
    public $lang_cn = false;
    /**
     * 随机角度
     * @var
     */
    public $random_angle_factor = 10; // random angle factor


    /**
     * Create a new securimage object, pass options to set in the constructor.<br />
     * This can be used to display a captcha, play an audible captcha, or validate an entry
     * @param array $options
     * <code>
     * $options = array(
     * 'text_color' => new Securimage_Color('#013020'),
     * 'code_length' => 5,
     * 'num_lines' => 5,
     * 'noise_level' => 3,
     * 'font_file' => Securimage::getPath() . '/custom.ttf'
     * );
     *
     * $img = new Securimage($options);
     * </code>
     */
    public function __construct($options = array())
    {
        $this->securimage_path = FONT_PATH;

        if (is_array($options) && sizeof($options) > 0) {
            foreach ($options as $prop => $val) {
                $this->$prop = $val;
            }
        }

        $this->image_bg_color = $this->initColor($this->image_bg_color, '#ffffff');
        $this->text_colorr = $this->initColor($this->text_color, '#616161');
        $this->line_color = $this->initColor($this->line_color, '#616161');
        $this->noise_color = $this->initColor($this->noise_color, '#616161');
        $this->signature_color = $this->initColor($this->signature_color, '#616161');

        if ($this->ttf_file == null) {
            $this->ttf_file = $this->securimage_path . 'arialbd.ttf';
        }

        $this->signature_font = $this->ttf_file;

        if ($this->code_length == null || $this->code_length < 1) {
            $this->code_length = 6;
        }

        if ($this->perturbation == null || !is_numeric($this->perturbation)) {
            $this->perturbation = 1;
        }

        if ($this->namespace == null || !is_string($this->namespace)) {
            $this->namespace = 'default';
        }

        // Initialize session or attach to existing
        if (session_id() == '') { // no session has been started yet, which is needed for validation
            if ($this->session_name != null && trim($this->session_name) != '') {
                session_name(trim($this->session_name)); // set session name if provided
            }
            session_start();
        }
    }

    /**
     * Used to serve a captcha image to the browser
     * @param string $background_image The path to the background image to use
     * <code>
     * $img = new Securimage();
     * $img->code_length = 6;
     * $img->num_lines   = 5;
     * $img->noise_level = 5;
     *
     * $img->show(); // sends the image to browser
     * exit;
     * </code>
     */
    public function show($background_image = '')
    {
        if ($background_image != '' && is_readable($background_image)) {
            $this->bgimg = $background_image;
        }

        $this->doImage();
    }

    /**
     * Check a submitted code against the stored value
     * @param string $code The captcha code to check
     * <code>
     * $code = $_POST['code'];
     * $img  = new Securimage();
     * if ($img->check($code) == true) {
     * $captcha_valid = true;
     * } else {
     * $captcha_valid = false;
     * }
     * </code>
     */
    public function check($code)
    {
        $this->code_entered = $code;
        $this->validate();
        return $this->correct_code;
    }

    /**
     * The main image drawing routing, responsible for constructing the entire image and serving it
     */
    protected function doImage()
    {
        if (($this->use_transparent_text == true || $this->bgimg != '') && function_exists('imagecreatetruecolor')) {
            $imagecreate = 'imagecreatetruecolor';
        } else {
            $imagecreate = 'imagecreate';
        }

        $this->im = $imagecreate($this->image_width, $this->image_height);
        $this->tmpimg = $imagecreate($this->image_width * $this->iscale, $this->image_height * $this->iscale);

        $this->allocateColors();
        imagepalettecopy($this->tmpimg, $this->im);

        $this->setBackground();

        $this->createCode();

        if ($this->noise_level > 0) {
            $this->drawNoise();
        }

        $this->drawWord();

        if ($this->perturbation > 0 && is_readable($this->ttf_file)) {
            if (1 == $this->iscale) {
                $this->sinCopy();
            } else {
                $this->distortedCopy();
            }
        }

        if ($this->num_lines > 0) {
            $this->drawLines();
        }

        $this->output();
    }

    /**
     * Allocate the colors to be used for the image
     */
    protected function allocateColors()
    {
        // allocate bg color first for imagecreate
        $this->gdbgcolor = imagecolorallocate($this->im, $this->image_bg_color->r, $this->image_bg_color->g,
            $this->image_bg_color->b);

        $alpha = intval($this->text_transparency_percentage / 100 * 127);

        if ($this->use_transparent_text == true) {
            $this->gdtextcolor = imagecolorallocatealpha($this->im, $this->text_colorr->r, $this->text_colorr->g,
                $this->text_colorr->b, $alpha);
            $this->gdlinecolor = imagecolorallocatealpha($this->im, $this->line_color->r, $this->line_color->g,
                $this->line_color->b, $alpha);
            $this->gdnoisecolor = imagecolorallocatealpha($this->im, $this->noise_color->r, $this->noise_color->g,
                $this->noise_color->b, $alpha);
        } else {
            $this->gdtextcolor = imagecolorallocate($this->im, $this->text_colorr->r, $this->text_colorr->g,
                $this->text_colorr->b);
            $this->gdlinecolor = imagecolorallocate($this->im, $this->line_color->r, $this->line_color->g,
                $this->line_color->b);
            $this->gdnoisecolor = imagecolorallocate($this->im, $this->noise_color->r, $this->noise_color->g,
                $this->noise_color->b);
        }

        $this->gdsignaturecolor = imagecolorallocate($this->im, $this->signature_color->r, $this->signature_color->g,
            $this->signature_color->b);

    }

    /**
     * The the background color, or background image to be used
     */
    protected function setBackground()
    {
        // set background color of image by drawing a rectangle since imagecreatetruecolor doesn't set a bg color
        imagefilledrectangle($this->im, 0, 0, $this->image_width, $this->image_height, $this->gdbgcolor);
        imagefilledrectangle($this->tmpimg, 0, 0, $this->image_width * $this->iscale,
            $this->image_height * $this->iscale, $this->gdbgcolor);

        if ($this->bgimg == '') {
            if ($this->background_directory != null && is_dir($this->background_directory) && is_readable($this->background_directory)) {
                $img = $this->getBackgroundFromDirectory();
                if ($img != false) {
                    $this->bgimg = $img;
                }
            }
        }

        if ($this->bgimg == '') {
            return;
        }

        $dat = @getimagesize($this->bgimg);
        if ($dat == false) {
            return;
        }

        switch ($dat[2]) {
            case 1:
                $newim = @imagecreatefromgif($this->bgimg);
                break;
            case 2:
                $newim = @imagecreatefromjpeg($this->bgimg);
                break;
            case 3:
                $newim = @imagecreatefrompng($this->bgimg);
                break;
            default:
                return;
        }

        if (!$newim) {
            return;
        }

        imagecopyresized($this->im, $newim, 0, 0, 0, 0, $this->image_width, $this->image_height, imagesx($newim),
            imagesy($newim));
    }

    /**
     * Scan the directory for a background image to use
     */
    protected function getBackgroundFromDirectory()
    {
        $images = array();

        if (($dh = opendir($this->background_directory)) !== false) {
            while (($file = readdir($dh)) !== false) {
                if (preg_match('/(jpg|gif|png)$/i', $file)) {
                    $images[] = $file;
                }
            }

            closedir($dh);

            if (sizeof($images) > 0) {
                return rtrim($this->background_directory, '/') . '/' . $images[rand(0, sizeof($images) - 1)];
            }
        }

        return false;
    }

    /**
     * Generates the code or math problem and saves the value to the session
     */
    protected function createCode()
    {
        $this->code = false;

        switch ($this->captcha_type) {
            case self::SI_CAPTCHA_MATHEMATIC: {
                $signs = array('+', '-', 'x');
                $left = rand(1, 10);
                $right = rand(1, 5);
                $sign = $signs[rand(0, 2)];

                switch ($sign) {
                    case 'x':
                        $c = $left * $right;
                        break;
                    case '-':
                        $c = $left - $right;
                        break;
                    default:
                        $c = $left + $right;
                        break;
                }

                $this->code = $c;
                $this->code_display = "$left $sign $right";
                break;
            }

            default: {
                $this->code = $this->generateCode($this->code_length);
                $this->code_display = $this->code;
                $this->code = ($this->case_sensitive) ? $this->code : strtolower($this->code);
            } // default
        }

        $this->saveData();
    }

    /**
     * Draws the captcha code on the image
     */
    protected function drawWord()
    {
        if (!is_readable($this->ttf_file)) {
            imagestring($this->im, 4, 10, ($this->image_height / 2) - 5, 'Failed to load TTF font file!',
                $this->gdtextcolor);
        } else {
            if ($this->perturbation > 0) {
                $width2 = $this->image_width * $this->iscale;
                $height2 = $this->image_height * $this->iscale;
                $so = $this->tmpimg;
            } else {
                $width2 = $this->image_width;
                $height2 = $this->image_height;
                $so = $this->im;
            }

            if ($this->lang_cn) {
                $this->ttf_file = $this->securimage_path . 'MSYH.TTF';
            }
            $font_size = $height2 * $this->text_scale * 1.1;
            $bb = imageftbbox($font_size, 0, $this->ttf_file, $this->code_display);
            $tx = $bb[4] - $bb[0];
            $ty = $bb[5] - $bb[1];
            $offset_x = 0;
            $y = round($height2 / 2 - $ty / 2 - $bb[1]);
            $x_ = floor($width2 / 2 - $tx / 2 - $bb[0]) * 0.5;
            for ($i = 0; $i < mb_strlen($this->code_display); $i++) {
                $angle = $this->random_angle();
                $x = $offset_x + $x_;
                $char = mb_substr($this->code_display, $i, 1);
                $text_color = $this->initColor($this->text_color, '#ffffff');
                $temp_color = imagecolorallocatealpha($this->im, $text_color->r, $text_color->g, $text_color->b, 0);
                imagettftext($so, $font_size, $angle, $x, $y, $temp_color, $this->ttf_file, $char);
                $offset_x += $this->offset_x * $this->iscale;
            }
        }

        // DEBUG
        //$this->im = $this->tmpimg;
        //$this->output();


    }

    /**
     * Copies the captcha image to the final image with distortion applied
     */
    protected function sinCopy()
    {
        $im_x = $this->image_width;
        $im_y = $this->image_height;
        imagepalettecopy($this->im, $this->tmpimg); // copy palette to final image so text colors come across
        for ($i = 0; $i < $im_x; $i++) {
            for ($j = 0; $j < $im_y; $j++) {
                $rgb = imagecolorat($this->tmpimg, $i, $j);
                if ((int)($i + 20 + sin($j / $im_y * 2 * M_PI) * 10) <= imagesx($this->im) && (int)($i + 20 + sin($j / $im_y * 2 * M_PI) * 10) >= 0) {
                    imagesetpixel($this->im, (int)($i + 10 + sin($j / $im_y * 2 * M_PI - M_PI * 0.1) * 4), $j, $rgb);
                }
            }
        }
    }

    /**
     * Copies the captcha image to the final image with distortion applied
     */
    protected function distortedCopy()
    {
        $numpoles = 3; // distortion factor
        // make array of poles AKA attractor points
        for ($i = 0; $i < $numpoles; ++$i) {
            $px[$i] = rand($this->image_width * 0.2, $this->image_width * 0.8);
            $py[$i] = rand($this->image_height * 0.2, $this->image_height * 0.8);
            $rad[$i] = rand($this->image_height * 0.2, $this->image_height * 0.8);
            $tmp = ((-$this->frand()) * 0.15) - .15;
            $amp[$i] = $this->perturbation * $tmp;
        }

        $bgCol = imagecolorat($this->tmpimg, 0, 0);
        $width2 = $this->iscale * $this->image_width;
        $height2 = $this->iscale * $this->image_height;
        imagepalettecopy($this->im, $this->tmpimg); // copy palette to final image so text colors come across
        // loop over $img pixels, take pixels from $tmpimg with distortion field
        for ($ix = 0; $ix < $this->image_width; ++$ix) {
            for ($iy = 0; $iy < $this->image_height; ++$iy) {
                $x = $ix;
                $y = $iy;
                for ($i = 0; $i < $numpoles; ++$i) {
                    $dx = $ix - $px[$i];
                    $dy = $iy - $py[$i];
                    if ($dx == 0 && $dy == 0) {
                        continue;
                    }
                    $r = sqrt($dx * $dx + $dy * $dy);
                    if ($r > $rad[$i]) {
                        continue;
                    }
                    $rscale = $amp[$i] * sin(3.14 * $r / $rad[$i]);
                    $x += $dx * $rscale;
                    $y += $dy * $rscale;
                }
                $c = $bgCol;
                $x *= $this->iscale;
                $y *= $this->iscale;
                if ($x >= 0 && $x < $width2 && $y >= 0 && $y < $height2) {
                    $c = imagecolorat($this->tmpimg, $x, $y);
                }
                if ($c != $bgCol) { // only copy pixels of letters to preserve any background image
                    imagesetpixel($this->im, $ix, $iy, $c);
                }
            }
        }
    }

    /**
     * Draws distorted lines on the image
     */
    protected function drawLines()
    {
        for ($line = 0; $line < $this->num_lines; ++$line) {
            $x = $this->image_width * (1 + $line) / ($this->num_lines + 1);
            $x += (0.5 - $this->frand()) * $this->image_width / $this->num_lines;
            $y = rand($this->image_height * 0.1, $this->image_height * 0.9);

            $theta = ($this->frand() - 0.5) * M_PI * 0.7;
            $w = $this->image_width;
            $len = rand($w * 0.4, $w * 0.7);
            $lwid = rand(0, 1);

            $k = $this->frand() * 0.6 + 0.2;
            $k = $k * $k * 0.5;
            $phi = $this->frand() * 6.28;
            $step = 0.5;
            $dx = $step * cos($theta);
            $dy = $step * sin($theta);
            $n = $len / $step;
            $amp = 1.5 * $this->frand() / ($k + 5.0 / $len);
            $x0 = $x - 0.5 * $len * cos($theta);
            $y0 = $y - 0.5 * $len * sin($theta);

            $ldx = round(-$dy * $lwid);
            $ldy = round($dx * $lwid);

            for ($i = 0; $i < $n; ++$i) {
                $x = $x0 + $i * $dx + $amp * $dy * sin($k * $i * $step + $phi);
                $y = $y0 + $i * $dy - $amp * $dx * sin($k * $i * $step + $phi);
                imagefilledrectangle($this->im, $x, $y, $x + $lwid, $y + $lwid, $this->gdlinecolor);
            }
        }
    }

    /**
     * Draws random noise on the image
     */
    protected function drawNoise()
    {
        if ($this->noise_level > 10) {
            $noise_level = 10;
        } else {
            $noise_level = $this->noise_level;
        }

        $noise_level *= 125; // an arbitrary number that works well on a 1-10 scale


        $points = $this->image_width * $this->image_height * $this->iscale;
        $height = $this->image_height * $this->iscale;
        $width = $this->image_width * $this->iscale;
        for ($i = 0; $i < $noise_level; ++$i) {
            $x = rand(10, $width);
            $y = rand(10, $height);
            $size = 1 == $this->iscale ? rand(1, 3) : rand(7, 10);
            if ($x - $size <= 0 && $y - $size <= 0) {
                continue;
            } // dont cover 0,0 since it is used by imagedistortedcopy
            imagefilledarc($this->tmpimg, $x, $y, $size, $size, 0, 360, $this->gdnoisecolor, IMG_ARC_PIE);
        }

        /*
            // DEBUG
            imagestring($this->tmpimg, 5, 25, 30, "$t", $this->gdnoisecolor);
            header('content-type: image/png');
            imagepng($this->tmpimg);
            exit;
            */
    }

    /**
     * Sends the appropriate image and cache headers and outputs image to the browser
     */
    protected function output()
    {
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");

        switch ($this->image_type) {
            case self::SI_IMAGE_JPEG:
                header("Content-Type: image/jpeg");
                imagejpeg($this->im, null, 90);
                break;
            case self::SI_IMAGE_GIF:
                header("Content-Type: image/gif");
                imagegif($this->im);
                break;
            default:
                header("Content-Type: image/png");
                imagepng($this->im);
                break;
        }

        imagedestroy($this->im);
        exit();
    }

    /**
     * Generates a random captcha code from the set character set
     */
    protected function generateCode()
    {
        $code = '';
        $charset = $this->lang_cn ? $this->charset_cn : $this->charset;
        for ($i = 1, $cslen = mb_strlen($charset); $i <= $this->code_length; ++$i) {
            $code .= mb_substr($charset, rand(0, $cslen - 1), 1);
        }
        return $code;
    }

    /**
     * Checks the entered code against the value stored in the session, handles case sensitivity
     * Also clears the stored codes if the code was entered correctly to prevent re-use
     */
    protected function validate()
    {
        $code = $this->getCode();
        // returns stored code, or an empty string if no stored code was found
        // checks the session 


        if ($this->case_sensitive == false && preg_match('/[A-Z]/', $code)) {
            // case sensitive was set from securimage_show.php but not in class
            // the code saved in the session has capitals so set case sensitive to true
            $this->case_sensitive = true;
        }

        $code_entered = trim((($this->case_sensitive) ? $this->code_entered : strtolower($this->code_entered)));
        $this->correct_code = false;

        if ($code != '') {
            if ($code == $code_entered) {
                $this->correct_code = true;
                $_SESSION['securimage_code_value'][$this->namespace] = '';
                $_SESSION['securimage_code_ctime'][$this->namespace] = '';
            }
        }
    }

    /**
     * Return the code from the session .  If none exists yet, an empty string is returned
     */
    protected function getCode()
    {
        $code = '';

        if (isset($_SESSION['securimage_code_value'][$this->namespace]) && trim($_SESSION['securimage_code_value'][$this->namespace]) != '') {
            if ($this->isCodeExpired($_SESSION['securimage_code_ctime'][$this->namespace]) == false) {
                $code = $_SESSION['securimage_code_value'][$this->namespace];
            }
        } else { /* no code stored in session , validation will fail */
        }

        return $code;
    }

    /**
     * Save data to session namespace
     */
    protected function saveData()
    {
        $_SESSION['securimage_code_value'][$this->namespace] = $this->code;
        $_SESSION['securimage_code_ctime'][$this->namespace] = time();
    }

    /**
     * Checks to see if the captcha code has expired and cannot be used
     * @param unknown_type $creation_time
     */
    protected function isCodeExpired($creation_time)
    {
        $expired = true;

        if (!is_numeric($this->expiry_time) || $this->expiry_time < 1) {
            $expired = false;
        } else {
            if (time() - $creation_time < $this->expiry_time) {
                $expired = false;
            }
        }

        return $expired;
    }

    function frand()
    {
        return 0.0001 * rand(0, 9999);
    }

    public function random_angle()
    {
        return ($this->random_angle_factor - mt_rand(0, $this->random_angle_factor * 2));
    }

    /**
     * Convert an html color code to a Securimage_Color
     * @param string $color
     * @param Securimage_Color $default The defalt color to use if $color is invalid
     */
    public function initColor($color, $default)
    {
        if (is_array($color)) {
            $color = $color[array_rand($color)];
        }
        if ($color == null) {
            return new Securimage_Color($default);
        } else {
            if (is_string($color)) {
                try {
                    return new Securimage_Color($color);
                } catch (Exception $e) {
                    return new Securimage_Color($default);
                }
            } else {
                if (is_array($color) && sizeof($color) == 3) {
                    return new Securimage_Color($color[0], $color[1], $color[2]);
                } else {
                    return new Securimage_Color($default);
                }
            }
        }
    }
}

/**
 * Color object for Securimage CAPTCHA
 *
 * @version 3.0
 * @since 2.0
 * @package Securimage
 * @subpackage classes
 *
 */
class Securimage_Color
{
    public $r;
    public $g;
    public $b;

    /**
     * Create a new Securimage_Color object.<br />
     * Constructor expects 1 or 3 arguments.<br />
     * When passing a single argument, specify the color using HTML hex format,<br />
     * when passing 3 arguments, specify each RGB component (from 0-255) individually.<br />
     * $color = new Securimage_Color('#0080FF') or <br />
     * $color = new Securimage_Color(0, 128, 255)
     *
     * @param string $color
     * @throws Exception
     */
    public function __construct($color = '#ffffff')
    {
        $args = func_get_args();

        if (sizeof($args) == 0) {
            $this->r = 255;
            $this->g = 255;
            $this->b = 255;
        } else {
            if (sizeof($args) == 1) {
                // set based on html code
                if (substr($color, 0, 1) == '#') {
                    $color = substr($color, 1);
                }

                if (strlen($color) != 3 && strlen($color) != 6) {
                    throw new InvalidArgumentException('Invalid HTML color code passed to Securimage_Color');
                }

                $this->constructHTML($color);
            } else {
                if (sizeof($args) == 3) {
                    $this->constructRGB($args[0], $args[1], $args[2]);
                } else {
                    throw new InvalidArgumentException('Securimage_Color constructor expects 0, 1 or 3 arguments; ' . sizeof($args) . ' given');
                }
            }
        }
    }

    /**
     * Construct from an rgb triplet
     * @param int $red The red component, 0-255
     * @param int $green The green component, 0-255
     * @param int $blue The blue component, 0-255
     */
    protected function constructRGB($red, $green, $blue)
    {
        if ($red < 0) {
            $red = 0;
        }
        if ($red > 255) {
            $red = 255;
        }
        if ($green < 0) {
            $green = 0;
        }
        if ($green > 255) {
            $green = 255;
        }
        if ($blue < 0) {
            $blue = 0;
        }
        if ($blue > 255) {
            $blue = 255;
        }

        $this->r = $red;
        $this->g = $green;
        $this->b = $blue;
    }

    /**
     * Construct from an html hex color code
     * @param string $color
     */
    protected function constructHTML($color)
    {
        if (strlen($color) == 3) {
            $red = str_repeat(substr($color, 0, 1), 2);
            $green = str_repeat(substr($color, 1, 1), 2);
            $blue = str_repeat(substr($color, 2, 1), 2);
        } else {
            $red = substr($color, 0, 2);
            $green = substr($color, 2, 2);
            $blue = substr($color, 4, 2);
        }

        $this->r = hexdec($red);
        $this->g = hexdec($green);
        $this->b = hexdec($blue);
    }
}

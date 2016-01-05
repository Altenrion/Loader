<?php
/**
 * Created by PhpStorm.
 * User: baryshevnv
 * Date: 08.11.2015
 * Time: 13:14
 *
 * **** What is a Loader ? ****
 *
 * This is a module for auto loading Js & css files dynamically.
 * You can ask for what. So the profit is :
 *
 * 1) no huge arrays in avery action
 * 2) load only those files that you are interested in.
 * 3) loading files with dependencies, without duplication, even
 *    if those files were already loaded.
 * 4) call for files in widget, action, view, or where ever you can =)
 * 5) load js files in footer or header only changing 1 flag =) !!!
 *
 * **** Prepare the Loader ****
 * 1) place event.php from this package, or kohana 2.4 to application/classes/
 *
 * 2) place loader.php in /application/classes/ directory
 *
 * 3) modify your base controller before() method like this:
 *
 *   public function after()
 *   {
 *      parent::after();
 *      Event::add('system.display', array('Loader', 'load_files'));
 *   }
 *
 * 4) modify index php (move execute() to bootstrap.php)
 * 5) modify bootstrap.php like this :
 *
 *     *** end ***
 *     $req = Request::factory()->execute()->send_headers()->body();
 *     Event::run('system.display', $req);
 *     echo $req;
 *
 *  now all should work! =)
 *
 * **** Using the Loader ****
 *
 * All of the methods defined by the Loader class are static, there is
 * no need to instantiate the class as an object.
 *
 *
 * **** Usage Examples ****
 *
 *
 *** simple loading css to head
 * Loader::load_css($files_css);
 *
 *** simple loding js to head of layout
 * Loader::load_js($files_js, Loader::POS_HEADER);
 *
 *** simple loding js to footer of layout
 * Loader::load_js($files_js, Loader::POS_FOOTER);
 *
 *** loding js to head of footer of layout, with loading dependencies
 * Loader::load_js($file, $position, true)->depends(array('jquery'))->init();
 *
 * Loader::load_js(array_of_files, Loader::POS_FOOTER, true)
 *             ->depends(array('jquery','ckeditor'))->init();
 *
 *** simple loding dependencies for some needs(not for outgoing usage)
 * Loader::load_dependencies();
 *
 *
 * @author     Baryshev Nikita
 * @copyright  (c) 2015- 2020 Altenrion
 *
 *
 * Remember about Helper_sorter::list_data method. Will place class or
 * method near the package for resolving dependency.
 *
 */
class Loader {

    const POS_HEADER = 'header';
    const POS_FOOTER = 'footer';

    protected static $condition_object;
    protected $files = array();
    protected $depends = array();
    protected $position;


    protected static $loading_dependencies;

    protected static $_css_files = array();
    protected static $_js_files = array(
        'header' => array(),
        'footer' => array(),
    );

    protected static $_custom_css = '';
    protected static $_custom_js = '';

    public static function load_js(array $files, $position = self::POS_FOOTER, $conditions = false)
    {
        if(empty($files))
            throw new Exception("Empty data for loading js files");

        if(!$conditions){
            self::$_js_files[$position] = array_unique(array_merge(self::$_js_files[$position], $files));
        }
        else{
            self::$condition_object = new loader();
            foreach ($files as $file) {
                self::$condition_object->files[] = $file;
                self::$condition_object->position = $position;
            }

            return self::$condition_object;
        }
    }

    public static function load_css($files)
    {
        if(empty($files))
            throw new Exception("Empty data for loading css files");

        self::$_css_files = array_unique(array_merge(self::$_css_files, $files));

    }

    public static function load_custom_css($script)
    {
        if(empty($script))
            throw new Exception("Empty data sent to Loader");

        self::$_custom_css .= '<style>'.$script.'</style>'.PHP_EOL;

    }

    public static function load_custom_js($script)
    {
        if(empty($script))
            throw new Exception("Empty data sent to Loader");

        self::$_custom_js .= '<script type="text/javascript">'.$script.'</script>'.PHP_EOL;
    }

    public function depends(array $dependencies)
    {
        if(empty($dependencies))
            throw new Exception("Empty dependencies for loading");

        foreach ($dependencies as $dependency) {
            self::$condition_object ->depends[] = $dependency;
        }

        return self::$condition_object;
    }

    public function init()
    {
        if(empty(self::$_js_files[self::POS_HEADER])){
            self::load_dependencies($this->depends);
            self::load_js($this->files, $this->position);
        }
        else{
            $new_js_dependencies = self::list_dependencies($this->depends, 'js');
            $new_files = $this->files;

            $loaded_deps  = array_intersect($new_js_dependencies, self::$_js_files[self::POS_HEADER]);
            if(!empty($loaded_deps)) {
                foreach ($loaded_deps as $k => $v) {
                    unset($new_js_dependencies[$k]);
                }
                if (!empty($new_js_dependencies))
                    self::load_js($new_js_dependencies, self::POS_HEADER);
            }else{
                self::load_js($new_js_dependencies, self::POS_HEADER);
            }

            $new_css_dependencies = self::list_dependencies($this->depends, 'css');
            $loaded_css  = array_intersect($new_css_dependencies, self::$_css_files);
            if(!empty($loaded_css)){
                foreach ($loaded_css as $k => $v) {
                    unset($new_css_dependencies[$k]);
                }
                if(!empty($new_css_dependencies))
                    self::load_css($new_css_dependencies);
            }else{
                self::load_css($new_css_dependencies);
            }

            $loaded_files = array_intersect($new_files, self::$_js_files[$this->position]);
            if(!empty($loaded_files)){
                foreach ($loaded_files as $k => $v) {
                    unset($new_files[$k]);
                }
                if(!empty($new_files))
                    self::load_js($new_files, $this->position);
            }else{
                self::load_js($new_files, $this->position);
            }
        }
    }


    public  static function load_dependencies(array $dependencies)
    {
        $js = self::list_dependencies($dependencies, 'js');
        self::load_js($js, self::POS_HEADER);

        $css = self::list_dependencies($dependencies, 'css');
        self::load_css($css);
    }

    public static function list_dependencies($dependencies, $type)
    {
        $deps = self::get_dependencies($dependencies);
        $files = Helper_Sorter::listData($deps,'name',$type);

        $list = array();

        foreach (array_filter($files) as $packs ) {
            $list = array_merge($list, $packs);
        }
        return $list;
    }

    public static function get_dependencies($dependencies = array())
    {
        if(!is_array($dependencies))
            throw new Exception("Dependencies must be array!");

        $all_dependencies = Kohana::$config->load('dependencies');

        if(empty($dependencies))
            return $all_dependencies;

        if(!empty($dependencies)){
            foreach ($dependencies as $dependency) {
                $deps[] = $all_dependencies->get($dependency);
            }
        }
        return $deps;
    }

    public static function render_layout_files($pos)
    {
        $js_head = $js_foot = $css = '';

        if(!empty(Loader::$_js_files[self::POS_HEADER]) || !empty(Loader::$_js_files[self::POS_FOOTER])){
            foreach (Loader::$_js_files as $position=>$js_pack) {
                $js[$position] = '';
                foreach ($js_pack as $file) {
                    $js[$position] .= html::script($file).PHP_EOL;
                }
            }
            $js_head = $js[self::POS_HEADER];
            $js_foot = $js[self::POS_FOOTER];
        }
        if(!empty(self::$_custom_js))
            $js_foot .= self::$_custom_js;


        if(!empty(Loader::$_css_files )){
            foreach (Loader::$_css_files as $k => $css_file) {
                $css .= html::style($css_file).PHP_EOL;
            }
        }
        if(!empty(self::$_custom_css))
            $css .= self::$_custom_css;


        $head = $css.PHP_EOL.$js_head.PHP_EOL;
        $foot = $js_foot.PHP_EOL;

        switch($pos){
            case 'header' : return $head; break;
            case 'footer' : return $foot; break;
        }
    }

    public static function load_files()
    {
        $head_files = Loader::render_layout_files(Loader::POS_HEADER);
        $footer_files = Loader::render_layout_files(Loader::POS_FOOTER);

        Event::$data = str_replace("</head>", $head_files."</head>", Event::$data);
        Event::$data = str_replace("</body>", $footer_files."</body>", Event::$data);

        Loader::clean_all();
    }

    public static function clean_all()
    {
        self::$_css_files = array();
        self::$_js_files = array();
        self::$_custom_css = array();
        self::$_custom_js = array();
    }
}

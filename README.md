# Loader

##  What is a Loader ? 
 
### This is a module for auto loading Js & css files dynamically. 
 You can ask for what. So the profit is : 
 
 1. no huge arrays in avery action  
 2. load only those files that you are interested in. 
 3. loading files with dependencies, without duplication, even 
    if those files were already loaded. 
 4. call for files in widget, action, view, or where ever you can =) 
 5. load js files in footer or header only changing 1 flag =) !!! 
 
## Prepare the Loader

1. place event.php from this package, or kohana 2.4 to application/classes/ 
2. place loader.php in /application/classes/ directory 
3. modify your base controller before() method like this: 
        public function after() 
        { 
         parent::after(); 
         Event::add('system.display', array('Loader', 'load_files')); 
        } 
4. modify index php (move execute() to bootstrap.php) 
5. modify bootstrap.php like this : 

 *** end *** 
 $req = Request::factory()->execute()->send_headers()->body(); 
 Event::run('system.display', $req); 
 echo $req; 
 
now all should work! =) 

## Using the Loader

All of the methods defined by the Loader class are static, there is 
no need to instantiate the class as an object. 
 

## Usage Examples

*** simple loading css to head 
 Loader::load_css($files_css); 

*** simple loding js to head of layout 
 Loader::load_js($files_js, Loader::POS_HEADER); 
 
*** simple loding js to footer of layout 
 Loader::load_js($files_js, Loader::POS_FOOTER); 
 
*** loding js to head of footer of layout, with loading dependencies 
 Loader::load_js($file, $position, true)->depends(array('jquery'))->init(); 
 
 Loader::load_js(array_of_files, Loader::POS_FOOTER, true) 
    ->depends(array('jquery','ckeditor'))->init(); 

*** simple loding dependencies for some needs(not for outgoing usage) 
 Loader::load_dependencies(); 


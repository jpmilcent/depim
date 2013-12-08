class BreadcrumbElement extends Object {
	String name = '';
	String classCss = '';
	String href = '';
	bool divider = false;
	
	String toString() {
	  return 'name=$name;classCss=$classCss;href=$href;divider=$divider';
	}
}
import "dart:io";
import "package:args/args.dart";
import 'package:polymer/builder.dart';

bool cleanBuild;
bool fullBuild;
bool useMachineInterface;

List<String> changedFiles;
List<String> removedFiles;

/**
 * If the file is named 'build.dart' and is placed in the root directory of a
 * project or in a directory containing a pubspec.yaml file, then the Editor
 * will automatically invoke that file whenever a file in that project changes.
 * See the source code of [processArgs] for information about the legal command
 * line options.
 */
void main() {
  processArgs();
	build(entryPoints: ['web/index.html'], options: parseOptions(['--deploy']));
	handleBuild();
	print("Build terminé");
}

void handleBuild() {
	if (cleanBuild) {
		// Faire quelque chose si la commande de nettoyage du build est lancée
	} else if (fullBuild) {
		// Faire quelque chose si la commande de nettoyage du build complet est lancée
		handleFullBuild();
	} else {
		handleChangedFiles(changedFiles);
		//handleRemovedFiles(removedFiles);
	}
}

/**
 * Handle --changed, --removed, --clean, --full, and --help command-line args.
 */
void processArgs() {
  var parser = new ArgParser()
  	..addOption("changed", help: "the file has changed since the last build",
      allowMultiple: true)
  	..addOption("removed", help: "the file was removed since the last build",
      allowMultiple: true)
  	..addFlag("clean", negatable: false, help: "remove any build artifacts")
  	..addFlag("full", negatable: false, help: "perform a full build")
  	..addFlag("machine",
    	negatable: false, help: "produce warnings in a machine parseable format")
  	..addFlag("help", negatable: false, help: "display this help and exit");

  var args = parser.parse(new Options().arguments);

  if (args["help"]) {
    print(parser.getUsage());
    exit(0);
  }

  changedFiles = args["changed"];
  removedFiles = args["removed"];

  useMachineInterface = args["machine"];

  cleanBuild = args["clean"];
  fullBuild = args["full"];
	print('oho'+args["changed"]	.toString());
}

/**
 * Recursively scan the current directory looking for .foo files to process.
 */
void handleFullBuild() {
  var files = <String>[];

  Directory.current.list(recursive: true).listen((entity) {
        if (entity is File) {
          files.add((entity as File).resolveSymbolicLinksSync());
        }
      },
      onDone: () {
				print('ala'+files.toString());
				handleChangedFiles(files);
      });
}

/**
 * Process the given list of changed files.
 */
void handleChangedFiles(List<String> files) {
	print("ici"+files.toString());
	files.forEach(_processFile);
}

/**
 * Convert a .foo file to a .foobar file.
 */
void _processFile(String arg) {
  print(arg.toString());
  if (arg.startsWith(new RegExp(r'docs/wiki/'))) {
    print("Compilation de la doc Markdown: ${arg}");
		//Process.run('/home/jpm/Applications/zim/zim.py', ['--export', '--format=markdown', '-o /home/jpm/dart/depim/docs/md/', 'depim'])
			//.then((ProcessResult results) {
    	//	print(results.stdout);
  		//});
  }
}


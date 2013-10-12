import "dart:io";

void main() {
	print("Build doc en cours");

	Process.run('/home/jpm/Applications/zim/zim.py',
		['--export', '--format=markdown', '-odocs/md/', 'depim'],
		runInShell: true)
		.then((ProcessResult results) {
			print("Génération doc Zim terminée");
			print(results.stdout);
			print(results.stderr);
  	});
}
TEXINPUTS := .:$(CURDIR)/include:${TEXINPUTS}
export TEXINPUTS

MAIN = manual-etva-cm
RUBBER_OPTS = -d -m hyperref

all: *.tex
	@if [ -x /usr/bin/rubber ]; then rubber $(RUBBER_OPTS) $(MAIN); else pdflatex $(MAIN) && pdflatex $(MAIN); fi

clean:
	@rm -f *.aux *.log *.toc *.lof *.out *.bak manual-etva-cm.pdf

dist-clean: clean
	@rm -f $(MAIN).pdf

spell:
	@aspell -c --encoding="utf-8" --lang=pt-pt -t etva-cm.tex

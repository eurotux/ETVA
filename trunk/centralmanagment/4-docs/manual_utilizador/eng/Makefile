TEXINPUTS := .:$(CURDIR)/include:${TEXINPUTS}
export TEXINPUTS

MAIN_ETVA = manual-etva-cm
MAIN_ETVM = manual-etvm-cm
RUBBER_OPTS = -d -m hyperref

all: *.tex
	@if [ -x /usr/bin/rubber ]; then rubber $(RUBBER_OPTS) $(MAIN_ETVA); else pdflatex $(MAIN_ETVA) && pdflatex $(MAIN_ETVA); fi
	@if [ -x /usr/bin/rubber ]; then rubber $(RUBBER_OPTS) $(MAIN_ETVM); else pdflatex $(MAIN_ETVM) && pdflatex $(MAIN_ETVM); fi

clean:
	@rm -f *.aux *.log *.toc *.lof *.out *.bak $(MAIN_ETVA).pdf $(MAIN_ETVM).pdf

dist-clean: clean
	@rm -f $(MAIN_ETVA).pdf $(MAIN_ETVM).pdf

spell:
	@aspell -c --encoding="utf-8" --lang=pt-pt -t *.tex

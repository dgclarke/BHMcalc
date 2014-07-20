
from util import *
from config import *

KeplerBinaries={
    "Model 1":dict(
        M1=1.0,
        M2=1.0,
        e=0.3,
        Pbin=20,
        ),

    "Model 2":dict(
        M1=1.0,
        M2=0.3,
        e=0.2,
        Pbin=10,
        ),

    "Model 3":dict(
        M1=0.7,
        M2=0.7,
        e=0.1,
        Pbin=7,
        ),

    "Kepler 16":dict(
        M1=0.65,
        M2=0.20,
        tau=0,
        R1=0.65,
        R2=0.23,
        T1=4450.0,
        T2=3291.0,
        abin=0.2243,
        e=0.15944,
        Pbin=41.079,
        Notes='',
        Line=['r','-'],
        Type='dK and dM',
        FigureXUV='5a1',
        FigureRs='5a2',
        ),
    "Kepler 38":dict(
        M1=0.95,
        M2=0.25,
        tau=0,
        R1=0.88,
        R2=0.27,
        T1=5640.0,
        T2=3374.0,
        abin=0.1469,
        e=0.032,
        Pbin=18.7953,
        Notes='',
        Line=['k','-'],
        Type='Solar-like and dM',
        FigureXUV='5b1',
        FigureRs='5b2',
        ),
    "Kepler 34":dict(
        M1=1.05,
        M2=1.02,
        tau=0,
        R1=1.16,
        R2=1.10,
        T1=5913.0,
        T2=5867.0,
        abin=0.22882,
        e=0.52087,
        Pbin=27.8,
        Notes='',
        Line=['b','-'],
        Type='Solar-like twins',
        FigureXUV='4a1',
        FigureRs='4a2',
        ),
    "Kepler 35":dict(
        M1=0.89,
        M2=0.81,
        tau=0,
        R1=1.03,
        R2=0.79,
        T1=5606.0,
        T2=5202.0,
        abin=0.1762,
        e=0.042,
        Pbin=20.734,
        Notes='',
        Line=['g','-'],
        Type='dK twins',
        FigureXUV='4c1',
        FigureRs='4c2',
        ),
    #See: http://arxiv.org/pdf/1208.5489v1.pdf
    "Kepler 47":dict(
        M1=1.04,
        M2=0.36,
        tau=0,
        R1=0.96,
        R2=0.35,
        T1=5636.0,
        T2=3357.0,
        abin=0.0836,
        e=0.02340,
        Pbin=7.448,
        Notes='',
        Line=['c','-'],
        Type='Solar-like and dM',
        FigureXUV='4b1',
        FigureRs='4b2',
        ),
     "Kepler 64":dict(
        M1=1.47,
        M2=0.37,
        tau='',
        R1=1.7,
        R2=0.34,
        T1=6200.0,
        T2=3390.0,
        abin=0.1769,
        e=0.204,
        Pbin=20.000214,
        Notes='',
        Line=['b','-'],
        Type='F and dM',
        FigureXUV='5c1',
        FigureRs='5c2',
        ),
    }
